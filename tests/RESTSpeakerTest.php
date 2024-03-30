<?php declare(strict_types=1);

/**
 * This file is part of RESTSpeaker, a PHP Experts, Inc., Project.
 *
 * Copyright © 2019-2024 PHP Experts, Inc.
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
use GuzzleHttp\Psr7\Request;
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

    public function setUp(): void
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

    public function testReturns_exact_unmodified_data_when_not_JSON()
    {
        $expectedBody = '<html lang="us">Hi</html>';
        $expected = new Response(200, ['Content-Type' => 'text/html'], $expectedBody);
        $this->guzzleHandler->append(
            $expected
        );

        $actual = $this->api->get('https://somewhere.com/');
        self::assertEquals($expectedBody, $actual);
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
    }

    public function testCanGetTheLastRawResponse()
    {
        // Test returns null with no request.
        self::assertSame(null, $this->api->getLastResponse());

        // Test normal requests.
        $statuses = [
            new Response(200, ['Content-Type' => 'application/json'], '{"hello": "world"}'),
            new Response(204, [], ''),
            new Response(400, [], ''),
        ];

        foreach ($statuses as $expected) {
            $expected = $statuses[0];
            $this->guzzleHandler->append($expected);
            $expectedJson = json_decode((string) $expected->getBody());

            $actualJSON = $this->api->get('https://somewhere.com/');
            $this->assertSame($expected, $this->api->getLastResponse());
            $this->assertEquals($expectedJson, $actualJSON);
        }
    }

    public function testCanGetTheLastStatusCode()
    {
        // Test returns -1 with no request.
        self::assertSame(-1, $this->api->getLastStatusCode());

        // Test normal requests.
        $statuses = [
            200 => new Response(200, ['Content-Type' => 'application/json'], '{"hello": "world"}'),
            204 => new Response(204, [], ''),
            400 => new Response(400, [], ''),
        ];

        foreach ($statuses as $expected => $statusResponse) {
            $this->guzzleHandler->append($statusResponse);

            $this->api->get('https://somewhere.com/');
            $this->assertSame($expected, $this->api->getLastStatusCode());
        }
    }

    /** @testdox Will automagically pass arrays as JSON via POST, PATCH and PUT. */
    public function testWillAutomagicallyPassJSONArrays()
    {
        $methods = ['post', 'patch', 'put'];
        foreach ($methods as $method) {
            $expectedJSON = sprintf('{"hello":"%s"}', $method);
            $expectedResponse = json_decode($expectedJSON);
            $payload = json_decode($expectedJSON, true);
            $this->guzzleHandler->append(new Response(200, ['Content-Type' => 'application/json'], $expectedJSON));

            $response = $this->api->$method("/$method", $payload);
            self::assertEquals($expectedResponse, $response);

            $actualRequest = $this->guzzleHandler->getLastRequest();
            self::assertInstanceOf(Request::class, $actualRequest);

            $actualJSON = (string) $actualRequest->getBody();
            self::assertEquals($expectedJSON, $actualJSON);
        }
    }

    /** @testdox Will automagically pass objects as JSON via POST, PATCH and PUT. */
    public function testWillAutomagicallyPassJSONObjects()
    {
        $methods = ['post', 'patch', 'put'];
        foreach ($methods as $method) {
            $expectedJSON = sprintf('{"hello":"%s"}', $method);
            $expectedResponse = json_decode($expectedJSON);
            $payload = json_decode($expectedJSON);
            $this->guzzleHandler->append(new Response(200, ['Content-Type' => 'application/json'], $expectedJSON));

            $response = $this->api->$method("/$method", $payload);
            self::assertEquals($expectedResponse, $response);

            $actualRequest = $this->guzzleHandler->getLastRequest();
            self::assertInstanceOf(Request::class, $actualRequest);

            $actualJSON = (string) $actualRequest->getBody();
            self::assertEquals($expectedJSON, $actualJSON);
        }
    }

    /** @testdox Implements Guzzle's PSR-18 ClientInterface interface. **/
    public function testImplementsGuzzlesClientInterface()
    {
        self::assertInstanceOf(\GuzzleHttp\ClientInterface::class, $this->api);
    }
}
