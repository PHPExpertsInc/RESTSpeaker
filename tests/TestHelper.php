<?php declare(strict_types=1);

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

namespace PHPExperts\RESTSpeaker\Tests;

use Dotenv\Dotenv;
use LogicException;
use PHPExperts\RESTSpeaker\RESTAuth;

final class TestHelper
{
    public static function loadTestEnv(array $configs)
    {
        // Create the temp .env.
        $tempFile = tempnam(sys_get_temp_dir(), 'resttest-');
        file_put_contents($tempFile, implode("\n", $configs));
        // Load Dotenv with the new .env.
        $dotenv = Dotenv::createImmutable(sys_get_temp_dir(), basename($tempFile));
        $dotenv->load();

        // Delete the temp file.
        unlink($tempFile);
    }

    public static function buildRESTAuthMock(): RESTAuth
    {
        return new class extends RESTAuth
        {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct()
            {
                $this->authMode = RESTAuth::AUTH_NONE;
            }

            protected function generateOAuth2TokenOptions(): array
            {
                return [];
            }

            /**
             * @return array The appropriate headers for passkey authorization.
             * @throws LogicException if the Zuora Rest Client is not configured in the .env file.
             */
            protected function generatePasskeyOptions(): array
            {
                return [];
            }
        };
    }
}
