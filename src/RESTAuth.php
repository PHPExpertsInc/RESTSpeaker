<?php declare(strict_types=1);

/**
 * This file is part of RESTSpeaker, a PHP Experts, Inc., Project.
 *
 * Copyright © 2019 PHP Experts, Inc.
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

abstract class RESTAuth
{
    /** OAuth2 Tokens are required for prod but unavailable in the dev sandbox. **/
    public const AUTH_MODE_PASSKEY = 'passkey';
    public const AUTH_MODE_TOKEN = 'token';
    public const AUTH_MODE_XAPI = 'xapi';

    /** @var RESTSpeaker */
    protected $api;

    /** @var string */
    protected $authMode;

    public function __construct(string $authStratMode, RESTSpeaker $apiClient = null)
    {
        if (!in_array($authStratMode, [self::AUTH_MODE_PASSKEY, self::AUTH_MODE_TOKEN])) {
            throw new LogicException('Invalid Zuora REST auth mode.');
        }

        $this->api = $apiClient;

        $this->authMode = $authStratMode;
    }

    public function setApiClient(RESTSpeaker $apiClient)
    {
        $this->api = $apiClient;
    }

    /**
     * @throws LogicException if token auth is attempted in an unsupported OAuth2 environment.
     * @throws RuntimeException if an OAuth2 Token could not be successfully generated.
     * @return array The appropriate headers for OAuth2 Tokens.
     */
    abstract protected function generateOAuthTokenHeader(): array;

    /**
     * @throws LogicException if the Zuora Rest Client is not configured in the .env file.
     * @return array The appropriate headers for passkey authorization.
     */
    abstract protected function generatePasskeyGuzzleOptions(): array;

    protected function generateXAPITokenOptions(): array
    {
        $apiKey = env('X_API_KEY');
        if (!$apiKey) {
            throw new LogicException('X_API_KEY has not been set in .env.');
        }

        return [
            'X-API-Key' => $apiKey,
        ];
    }

    public function generateGuzzleAuthOptions(): array
    {
        if ($this->authMode === self::AUTH_MODE_TOKEN) {
            return $this->generateOAuthTokenHeader();
        }
        elseif ($this->authMode === self::AUTH_MODE_PASSKEY) {
            return $this->generatePasskeyGuzzleOptions();
        }
        elseif ($this->authMode === self::AUTH_MODE_XAPI) {
            return $this->generateXAPITokenOptions();
        }

        throw new LogicException('Invalid REST auth mode.');
    }
}
