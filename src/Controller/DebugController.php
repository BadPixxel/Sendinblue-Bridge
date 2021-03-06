<?php

/*
 *  Copyright (C) 2020 BadPixxel <www.badpixxel.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace BadPixxel\SendinblueBridge\Controller;

use BadPixxel\SendinblueBridge\Interfaces\MjmlTemplateProviderInterface;
use BadPixxel\SendinblueBridge\Models\AbstractEmail;
use BadPixxel\SendinblueBridge\Services\TemplateManager as Manager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Emails Templates Debugging Actions.
 */
class DebugController extends AbstractController
{
    /**
     * @var string
     */
    const TMPL_DIR = "/var/";

    /**
     * @var string
     */
    const TMPL_PATH = "/sib_email_template.html.twig";

    /**
     * Debug of a Partial Email Mjml Template Block
     *
     * @Route("/{emailCode}/mjml/{tmplCode}", name="badpixxel_sendinblue_tmpl_debug_mjml")
     *
     * @param Manager     $manager
     * @param string      $emailCode Email Code for Parameters Generation
     * @param string      $tmplCode  Twig Source Mjml Block Template
     * @param null|string $styles    Twig Source Mjml Styles
     *
     * @return Response
     */
    public function mjmlAction(Manager $manager, string $emailCode, string $tmplCode, string $styles = null): Response
    {
        //==============================================================================
        // Identify Email Class
        $emailClass = $manager->getEmailByCode($emailCode);
        if (is_null($emailClass)) {
            return new Response("Error: Email Class not Found");
        }
        if (!class_exists($emailClass) || !$manager->isTemplateAware($emailClass)) {
            return new Response("Error: Email Class not Found");
        }
        //==============================================================================
        // Load Mjml Convert
        $mjmlConverter = $manager->getMjmlConverter();
        if (!$mjmlConverter) {
            return new Response("Error: Mjml Converter not Available");
        }
        /** @var Kernel $kernel */
        $kernel = $this->get('kernel');
        //==============================================================================
        // Compile Mjml Block Template to Html
        /** @var EngineInterface $twig */
        $twig = $this->get('templating');
        $tmplMjml = (string) $twig->render("@SendinblueBridge/Debug/mjml_block.html.twig", array(
            "tmplStyles" => $styles,
            "tmplPath" => $tmplCode,
            "tmplParams" => array(),
        ));
        $tmplHtml = $mjmlConverter->toHtml($tmplMjml);
        $tmplPath = $kernel->getProjectDir().self::TMPL_DIR;
        $tmplName = 'sib_'.md5($tmplCode).'.html.twig';
        //==============================================================================
        // Store Template Html to Disk
        file_put_contents($tmplPath.$tmplName, $tmplHtml);
        //==============================================================================
        // Add Temporary Path to Twig Loader
        /** @var Environment $twig */
        $twig = $this->get('twig');
        /** @var FilesystemLoader $loader */
        $loader = $twig->getLoader();
        $loader->addPath($tmplPath);

        /** @var UserInterface $user */
        $user = $this->getUser();

        return $this->render($tmplName, $manager->getTmplParameters($emailClass, $user));
    }

    /**
     * Debug of a Complete Mjml Email
     *
     * @param Manager       $tmplManager
     * @param AbstractEmail $email
     *
     * @return Response
     */
    public function emailAction(Manager $tmplManager, AbstractEmail $email): Response
    {
        //==============================================================================
        // Safety Check
        if (!$tmplManager->isTemplateAware(get_class($email))) {
            return new Response("Error: Email Class not Template Aware");
        }
        if (!($email instanceof MjmlTemplateProviderInterface)) {
            return new Response("Error: Email Class not Template Aware");
        }
        //==============================================================================
        // Load Mjml Convert
        $mjmlConverter = $tmplManager->getMjmlConverter();
        if (!$mjmlConverter) {
            return new Response("Error: Mjml Converter not Available");
        }
        /** @var Kernel $kernel */
        $kernel = $this->get('kernel');
        //==============================================================================
        // Compile Mjml Template to Html
        $tmplHtml = $mjmlConverter->toHtml($email::getTemplateHtml());
        $tmplPath = $kernel->getProjectDir().self::TMPL_DIR;
        $tmplName = 'sib_'.md5(get_class($email)).'.html.twig';
        //==============================================================================
        // Store Template Html to Disk
        file_put_contents($tmplPath.$tmplName, $tmplHtml);
        //==============================================================================
        // Add Temporary Path to Twig Loader
        /** @var Environment $twig */
        $twig = $this->get('twig');
        /** @var FilesystemLoader $loader */
        $loader = $twig->getLoader();
        $loader->addPath($tmplPath);
        //==============================================================================
        // Render Email Html Preview
        return $this->render(
            $tmplName,
            array("params" => $email->getEmail()->getParams())
        );
    }
}
