<?php declare(strict_types=1);

namespace PHPExperts\RESTSpeaker\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use LogicException;
use PHPExperts\RESTSpeaker\HTTPSpeaker;
use PHPExperts\RESTSpeaker\RESTAuth;
use PHPExperts\RESTSpeaker\RESTSpeaker;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RESTAuthTest extends TestCase
{
    public function testCannotBuildItself()
    {
        $expectedError = 'Cannot instantiate abstract class ' . RESTAuth::class;

        try {
            $api = new RESTAuth();
            $this->fail('Error: Somehow instantiated RESTAuth');
        } catch (\Error $e) {
            self::assertEquals($expectedError, $e->getMessage());
        }
    }

    public static function buildRestAuthMock(): RESTAuth
    {
        return new class(RESTAuth::AUTH_NONE) extends RESTAuth
        {
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

        $restAuthMock = RESTSpeakerTest::buildRESTAuthMock();
        $expectedClient = new RESTSpeaker($restAuthMock);

        $restAuth->setApiClient($expectedClient);
        $this->assertSame($expectedClient, $restAuth->getApiClient());
    }

    public function testSupportsNoAuth()
    {
        $restAuth = self::buildRestAuthMock();
        $expected = [];
        $actual = $restAuth->generateGuzzleAuthOptions();

        self::assertEquals($actual, $expected);
    }
}
