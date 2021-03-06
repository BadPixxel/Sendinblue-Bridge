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

namespace BadPixxel\SendinblueBridge\Services;

use SendinBlue\Client\Model\SendSmtpEmailReplyTo;
use SendinBlue\Client\Model\SendSmtpEmailSender;
use Symfony\Component\Routing\RouterInterface as Router;

/**
 * Bridge Configuration Manager for SendingBlue Api.
 */
class ConfigurationManager
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @param array $configuration
     * @param bool  $enabled
     */
    public function __construct(array $configuration, bool $enabled)
    {
        $this->config = $configuration;
        $this->enabled = $enabled;
    }

    /**
     * Allow Sending Emails ?
     *
     * @return bool
     */
    public function isSendAllowed(): bool
    {
        return $this->enabled;
    }

    /**
     * Allow Auto-Refresh of Emails Metadata?
     *
     * @return bool
     */
    public function isRefreshMetadataAllowed(): bool
    {
        return $this->config["refresh"]["metadata"];
    }

    /**
     * Allow Auto-Refresh of Emails Contents?
     *
     * @return bool
     */
    public function isRefreshContentsAllowed(): bool
    {
        return $this->config["refresh"]["contents"];
    }

    /**
     * Allowed Mjml API ?
     *
     * @return bool
     */
    public function isMjmlAllowed(): bool
    {
        if (!isset($this->config["mjml"]["endpoint"]) || empty($this->config["mjml"]["endpoint"])) {
            return false;
        }
        if (!isset($this->config["mjml"]["api_key"]) || empty($this->config["mjml"]["api_key"])) {
            return false;
        }
        if (!isset($this->config["mjml"]["secret_key"]) || empty($this->config["mjml"]["secret_key"])) {
            return false;
        }

        return true;
    }

    /**
     * Get Email Storage Class.
     *
     * @return string
     */
    public function getEmailStorageClass(): string
    {
        return $this->config['storage']['emails'];
    }

    /**
     * Get Sms Storage Class.
     *
     * @return string
     */
    public function getSmsStorageClass(): string
    {
        return $this->config['storage']['sms'];
    }

    /**
     * Get Default Email Sender.
     *
     * @return SendSmtpEmailSender
     */
    public function getDefaultSender(): SendSmtpEmailSender
    {
        return new SendSmtpEmailSender(array(
            'name' => $this->config['sender']['name'],
            'email' => $this->config['sender']['email'],
        ));
    }

    /**
     * Get Default Email Sender.
     *
     * @return SendSmtpEmailReplyTo
     */
    public function getDefaultReplyTo(): SendSmtpEmailReplyTo
    {
        return new SendSmtpEmailReplyTo(array(
            'name' => $this->config['reply']['name'],
            'email' => $this->config['reply']['email'],
        ));
    }

    /**
     * Find an Email Class by Code
     *
     * @param string $emailCode
     *
     * @return null|string
     */
    public function getEmailByCode(string $emailCode): ?string
    {
        return isset($this->config['emails'][$emailCode])
            ? $this->config['emails'][$emailCode]
            : null;
    }

    /**
     * Get All Emails Class
     *
     * @return array
     */
    public function getAllEmails(): array
    {
        return $this->config['emails'];
    }

    /**
     * Override Current Router Config if we are in CLI Mode (Tests)
     *
     * @param Router $router
     *
     * @return Router
     */
    public function configureRouter(Router $router): Router
    {
        $context = $router->getContext();
        if ("localhost" == $context->getHost()) {
            $context->setHost((string) parse_url($this->config['cli_host'], PHP_URL_HOST));
            $context->setScheme((string) parse_url($this->config['cli_host'], PHP_URL_SCHEME));
        }

        return $router;
    }

    /**
     * Get Mjml API Endpoint
     *
     * @return string
     */
    public function getMjmlEndpoint(): string
    {
        return (string) $this->config["mjml"]["endpoint"];
    }

    /**
     * Get Mjml API Auth
     *
     * @return string
     */
    public function getMjmlAuth(): string
    {
        return (string) $this->config["mjml"]["api_key"].":".$this->config["mjml"]["secret_key"];
    }
}
