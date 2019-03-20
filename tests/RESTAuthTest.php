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

namespace PHPExperts\RESTSpeaker\Tests;

use Error;
use LogicException;
use PHPExperts\RESTSpeaker\RESTAuth;
use PHPExperts\RESTSpeaker\RESTSpeaker;
use PHPUnit\Framework\TestCase;

class RESTAuthTest extends TestCase
{
    public function testCannotBuildItself()
    {
        $expectedError = 'Cannot instantiate abstract class ' . RESTAuth::class;

        try {
            /** @noinspection PhpParamsInspection */
            new RESTAuth();
            self::fail('Error: Somehow instantiated RESTAuth');
        } catch (Error $e) {
            self::assertEquals($expectedError, $e->getMessage());
        }
    }

    public static function buildRestAuthMock(string $authMode = RESTAuth::AUTH_NONE): RESTAuth
    {
        return new class($authMode) extends RESTAuth
        {
            public const AUTH_MODE_DOESNT_EXIST = 'Non-existent';

            public const AUTH_MODES = [
                RESTAuth::AUTH_NONE,
                RESTAuth::AUTH_MODE_PASSKEY,
                RESTAuth::AUTH_MODE_OAUTH2,
                RESTAuth::AUTH_MODE_XAPI,
                RESTAuth::AUTH_MODE_CUSTOM,
                self::AUTH_MODE_DOESNT_EXIST,
            ];

            protected function generateOAuth2TokenOptions(): array
            {
                return [];
            }

            protected function generatePasskeyOptions(): array
            {
                return [];
            }
        };
    }

    public function testChildrenCanBuildThemselves()
    {
        $childAuth = self::buildRestAuthMock();

        self::assertInstanceOf(RESTAuth::class, $childAuth);
    }

    public function testWillNotAllowInvalidAuthModes()
    {
        $this->expectException(LogicException::class);

        self::buildRestAuthMock('invalid auth');
    }

    public function testCanSetACustomApiClient()
    {
        $restAuth = new class(RESTAuth::AUTH_NONE) extends RESTAuth
        {
            protected function generateOAuth2TokenOptions(): array
            {
                return [];
            }

            protected function generatePasskeyOptions(): array
            {
                return [];
            }

            public function getApiClient(): ?RESTSpeaker
            {
                return $this->api;
            }
        };

        self::assertNull($restAuth->getApiClient());

        $restAuthMock = TestHelper::buildRESTAuthMock();
        $expectedClient = new RESTSpeaker($restAuthMock);

        $restAuth->setApiClient($expectedClient);
        self::assertSame($expectedClient, $restAuth->getApiClient());
    }

    public function testWontCallANonexistingAuthStrat()
    {
        $this->expectException(LogicException::class);

        $restAuth = self::buildRestAuthMock('Non-existent');
        $restAuth->generateGuzzleAuthOptions();
    }

    public function testSupportsNoAuth()
    {
        $restAuth = self::buildRestAuthMock();
        $expected = [];
        $actual = $restAuth->generateGuzzleAuthOptions();

        self::assertEquals($actual, $expected);
    }

    public function testSupports_XAPI_Token_auth()
    {
        $restAuth = self::buildRestAuthMock(RESTAuth::AUTH_MODE_XAPI);

        // 1. Make sure that it fails if X-API-Key isn't configured.
        try {
            $restAuth->generateGuzzleAuthOptions();
            self::fail('XAPIToken auth did not blow up, even tho it was not configured.');
        }
        catch (LogicException $e) {
            self::assertEquals('X_API_KEY has not been set in .env.', $e->getMessage());
        }

        // 2. Actually test it.
        TestHelper::loadTestEnv(['X_API_KEY=mySecret']);

        $expected = [
            "X-API-Key" => "mySecret"
        ];
        $actual =$restAuth->generateGuzzleAuthOptions();
        self::assertEquals($expected, $actual);
    }

    public function testSupportsCustomAuthStrategies()
    {
        try {
            $restAuth = self::buildRestAuthMock(RESTAuth::AUTH_MODE_CUSTOM);
            $restAuth->generateGuzzleAuthOptions();
            $this->fail('The base RestAuth custom auth was called and no exception was thrown.');
        }
        catch (LogicException $e) {
            self::assertEquals(
                'The base RestAuth custom auth should not be called.',
                $e->getMessage(),
                'Something is wrong. The test is probably bugged.'
            );
        }
    }
}
