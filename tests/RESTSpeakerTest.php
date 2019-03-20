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

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPExperts\RESTSpeaker\HTTPSpeaker;
use PHPExperts\RESTSpeaker\RESTSpeaker;
use PHPUnit\Framework\TestCase;

class RESTSpeakerTest extends TestCase
{
    /** @var RESTSpeaker */
    protected $api;

    /** @var MockHandler */
    protected $guzzleHandler;

    public function setUp()
    {
        parent::setUp();

        $restAuthMock = TestHelper::buildRESTAuthMock();

        $this->guzzleHandler = new MockHandler();

        $http = new HTTPSpeaker('', new GuzzleClient(['handler' => $this->guzzleHandler]));

        $this->api = new RESTSpeaker($restAuthMock, '', $http);
    }

    public function testCanBuildItself()
    {
        $api = new RESTSpeaker(TestHelper::buildRESTAuthMock());
        self::assertInstanceOf(RESTSpeaker::class, $api);
    }

    public function testReturnsNullWhenNoContent()
    {
        $this->guzzleHandler->append(
            new Response(
                204, // HTTP/204: No Content
                ['Content-Type' => 'application/json'],
                null
            )
        );

        $actual = $this->api->get('/no-data');
        self::assertNull($actual);
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

    public function testJSON_URLs_return_plain_PHP_arrays()
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

    public function testRequestsApplicationJsonContentType()
    {
        $this->guzzleHandler->append(
            new Response(200, [], '')
        );

        $this->api->get('https://somewhere.com/');
        $requestHeaders = $this->guzzleHandler->getLastRequest()->getHeaders();

        $expected = 'application/json';
        self::assertEquals($expected, $requestHeaders['Content-Type'][0]);
    }}
