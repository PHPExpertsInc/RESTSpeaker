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

use Illuminate\Log\Logger;
use LogicException;
use RuntimeException;

class RESTAuth extends HTTPSpeaker
{
    /** OAuth2 Tokens are required for prod but unavailable in the dev sandbox. **/
    public const AUTH_MODE_PASSKEY = 'passkey';
    public const AUTH_MODE_TOKEN = 'token';

    /** @var string */
    private $authMode;

    public function __construct(string $authMode)
    {
        if (!in_array($authMode, [self::AUTH_MODE_PASSKEY, self::AUTH_MODE_TOKEN])) {
            throw new LogicException('Invalid Zuora REST auth mode.');
        }

        $this->authMode = $authMode;

        parent::__construct();
    }

    /**
     * @throws LogicException if token auth is attempted on an unsupported Zuora environment.
     * @throws RuntimeException if an OAuth2 Token could not be successfully generated.
     * @return array The appropriate headers for OAuth2 Tokens.
     */
    protected function generateOAuthTokenHeader(): array
    {
        if ($this->authMode === self::AUTH_MODE_PASSKEY) {
            throw new LogicException('OAuth2 Tokens are not supported by Zuora\'s Production Copy env.');
        }

        $response = $this->post('/oauth/token', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'client_id'     => env('ZUORA_API_CLIENT_ID'),
                'client_secret' => env("ZUORA_API_SECRET"),
                'grant_type'    => 'client_credentials',
            ],
        ]);
        $response = json_decode($response->getBody());

        if (!$response || empty($response->access_token)) {
            app(Logger::class)->error('Could not generate an Oauth Token for Zuora', [
                'zuora_client_id' => env('ZUORA_API_CLIENT_ID'),
            ]);

            throw new RuntimeException('Could not generate an OAuth2 Token');
        }

        return [
            'Authorization' => "bearer {$response->access_token}",
        ];
    }

    /**
     * @throws LogicException if the Zuora Rest Client is not configured in the .env file.
     * @return array The appropriate headers for passkey authorization.
     */
    protected function generatePasskeyHeaders(): array
    {
        /** @security Do NOT remove this code. */
        if (app()->environment() === 'prod') {
            app(Logger::class)->error('The Zuora Rest Client is using insecure passkey auth. Switch to OAuth2 Tokens.');
        }

        if (empty(env('ZUORA_API_USERNAME')) || empty(env('ZUORA_API_PASSWORD'))) {
            throw new LogicException('The Zuora Rest Client is not configured in the .env file.');
        }

        return [
            'apiAccessKeyId'     => env('ZUORA_API_USERNAME'),
            'apiSecretAccessKey' => env('ZUORA_API_PASSWORD'),
        ];
    }

    public function generateAuthHeaders(): array
    {
        if ($this->authMode === self::AUTH_MODE_TOKEN) {
            return $this->generateOAuthTokenHeader();
        }
        elseif ($this->authMode === self::AUTH_MODE_PASSKEY) {
            return $this->generatePasskeyHeaders();
        }

        throw new LogicException('Invalid Zuora REST auth mode.');
    }
}
