<?php

/**
 * This file is part of RESTSpeaker, a PHP Experts, Inc., Project.
 *
 * Copyright © 2019-2020 PHP Experts, Inc.
 * Author: Theodore R. Smith <theodore@phpexperts.pro>
 *  GPG Fingerprint: 4BF8 2613 1C34 87AC D28F  2AD8 EB24 A91D D612 5690
 *  https://www.phpexperts.pro/
 *  https://github.com/phpexpertsinc/RESTSpeaker
 *
 * This file is licensed under the MIT License.
 */

namespace PHPExperts\RESTSpeaker;

use LogicException;
use RuntimeException;

abstract class RESTAuth implements RESTAuthDriver
{
    const AUTH_NONE = 'NoAuth';
    /** OAuth2 Tokens are required for prod but unavailable in the dev sandbox. **/
    const AUTH_MODE_PASSKEY = 'Passkey';
    const AUTH_MODE_OAUTH2 = 'OAuth2Token';
    const AUTH_MODE_XAPI = 'XAPIToken';
    const AUTH_MODE_CUSTOM = 'CustomAuth';

    const AUTH_MODES = [
        self::AUTH_NONE,
        self::AUTH_MODE_PASSKEY,
        self::AUTH_MODE_OAUTH2,
        self::AUTH_MODE_XAPI,
        self::AUTH_MODE_CUSTOM,
    ];

    /** @var RESTSpeaker|null */
    protected $api;

    /** @var string */
    protected $authMode;

    public function __construct($authStratMode, RESTSpeaker $apiClient = null)
    {
        if (!in_array($authStratMode, static::AUTH_MODES)) {
            throw new LogicException('Invalid REST auth mode.');
        }

        $this->api = $apiClient;

        $this->authMode = $authStratMode;
    }

    public function setApiClient(RESTSpeaker $apiClient)
    {
        $this->api = $apiClient;
    }

    protected function generateNoAuthOptions()
    {
        return [];
    }

    protected function generateCustomAuthOptions()
    {
        throw new LogicException('The base RestAuth custom auth should not be called.');
    }

    /**
     * @throws LogicException if token auth is attempted in an unsupported OAuth2 environment.
     * @throws RuntimeException if an OAuth2 Token could not be successfully generated.
     * @return array The appropriate headers for OAuth2 Tokens.
     */
    abstract protected function generateOAuth2TokenOptions();

    /**
     * @return array The appropriate headers for passkey authorization.
     */
    abstract protected function generatePasskeyOptions();

    protected function generateXAPITokenOptions()
    {
        $apiKey = env('X_API_KEY');
        if (!$apiKey) {
            throw new LogicException('X_API_KEY has not been set in .env.');
        }

        return [
            'headers' => [
                'X-API-Key' => $apiKey,
            ],
        ];
    }

    public function generateGuzzleAuthOptions()
    {
        $handler = "generate{$this->authMode}Options";
        if (!is_callable([$this, $handler])) {
            throw new LogicException('Invalid REST auth mode.');
        }

        return $this->$handler();
    }
}

if (!function_exists('env')) {
    function env($key, $default = '')
    {
        $value = getenv($key);
        if ($value === null) {
            $value = $default;
        }

        return $value;
    }
}
