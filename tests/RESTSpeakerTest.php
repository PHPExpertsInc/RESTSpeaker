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

class RESTSpeakerTest extends TestCase
{
    /** @var RESTSpeaker */
    protected $api;

    /** @var MockHandler */
    protected $guzzleHandler;

    public static function buildRESTAuthMock(): RESTAuth
    {
        return new class extends RESTAuth
        {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct()
            {
                $this->authMode = RESTAuth::AUTH_MODE_OAUTH2;
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

    public function setUp()
    {
        parent::setUp();

        $restAuthMock = self::buildRESTAuthMock();

        $this->guzzleHandler = new MockHandler();

        $http = new HTTPSpeaker('', new GuzzleClient(['handler' => $this->guzzleHandler]));

        $this->api = new RESTSpeaker($restAuthMock, '', $http);
    }

    public function testCanBuildItself()
    {
        $api = new RESTSpeaker(self::buildRESTAuthMock());
        $this->assertInstanceOf(RESTSpeaker::class, $api);
    }

    public function testReturnsNullWhenNoContent()
    {
        $this->guzzleHandler->append(
            new Response(
                204, // HTTP/204: No Content
                ['Content-Type' => 'application/json'],
                null,
            )
        );

        $actual = $this->api->get('/no-data');
        $this->assertNull($actual);
    }

    public function testWorks_as_a_Guzzle_proxy_when_not_JSON()
    {
        $expectedBody = '<html lang="us">Hi</html>';
        $expected = new Response(200, ['Content-Type' => 'text/html'], $expectedBody);
        $this->guzzleHandler->append(
            $expected
        );

        $actual = $this->api->get('https://somewhere.com/');
        self::assertEquals($expected, $actual);
        self::assertEquals($expectedBody, $actual->getBody());
    }

    public function testDecodes_JSON_URLs()
    {
        $expected = [
            'decoded' => 'json',
            'hmm' => [
                'nested',
                'array',
                1,
                2.0,
            ],
        ];

        $json = json_encode($expected);
        $this->guzzleHandler->append(
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                $json
            )
        );

        $expected = json_decode($json);
        $actual = $this->api->get('https://somewhere.com/');
        self::assertEquals($expected, $actual);
    }

    public function testCan_fall_down_to_HTTPSpeaker()
    {
        $expectedBody = json_encode([
            'decoded' => 'json',
            'hmm' => [
                'nested',
                'array',
                1,
                2.0,
            ],
        ]);

        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            $expectedBody
        );

        $this->guzzleHandler->append(
            $response
        );

        $actual = $this->api->http->get('https://somewhere.com/');
        self::assertEquals($response, $actual);
        self::assertEquals($expectedBody, $actual->getBody());
    }
}
