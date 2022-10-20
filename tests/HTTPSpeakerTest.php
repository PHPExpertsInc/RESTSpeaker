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

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPExperts\RESTSpeaker\HTTPSpeaker;
use PHPUnit\Framework\TestCase;

class HTTPSpeakerTest extends TestCase
{
    /** @var HTTPSpeaker */
    protected $http;

    /** @var MockHandler */
    protected $guzzleHandler;

    public function setUp(): void
    {
        parent::setUp();

        $this->guzzleHandler = new MockHandler();

        $this->http = new HTTPSpeaker('', new GuzzleClient(['handler' => $this->guzzleHandler]));
    }

    public function testWorks_as_a_Guzzle_proxy()
    {
        $expectedBody = '<html lang="us">Hi</html>';
        $expected = new Response(200, ['Content-Type' => 'text/html'], $expectedBody);
        $this->guzzleHandler->append(
            $expected
        );

        $actual = $this->http->get('https://somewhere.com/');
        self::assertEquals($expected, $actual);
        self::assertEquals($expectedBody, $actual->getBody());
    }

    public function testIdentifiesAsItsOwnUserAgent()
    {
        $this->guzzleHandler->append(
            new Response(200, ['Content-Type' => 'text/html'], '')
        );

        $this->http->get('https://somewhere.com/');
        $requestHeaders = $this->guzzleHandler->getLastRequest()->getHeaders();

        $phpV = phpversion();
        $expected = "PHPExperts/RESTSpeaker-2.4 (PHP {$phpV})";
        self::assertEquals($expected, $requestHeaders['User-Agent'][0]);
    }

    public function testRequestsTextHtmlContentType()
    {
        $this->guzzleHandler->append(
            new Response(200, [], '')
        );

        $this->http->get('https://somewhere.com/');
        $requestHeaders = $this->guzzleHandler->getLastRequest()->getHeaders();

        $expected = 'text/html';
        self::assertEquals($expected, $requestHeaders['Content-Type'][0]);
    }

    public function testCanGetTheLastRawResponse()
    {
        // Test returns null with no request.
        self::assertSame(null, $this->http->getLastResponse());

        // Test normal requests.
        $statuses = [
            new Response(200, ['Content-Type' => 'application/json'], '{"hello": "world"}'),
            new Response(204, [], ''),
            new Response(400, [], ''),
        ];

        foreach ($statuses as $status) {
            $this->guzzleHandler->append($status);

            $expected = $this->http->get('https://somewhere.com/');
            $this->assertSame($expected, $this->http->getLastResponse());
        }
    }

    public function testCanGetTheLastStatusCode()
    {
        // Test returns -1 with no request.
        self::assertSame(-1, $this->http->getLastStatusCode());

        // Test normal requests.
        $statuses = [
            new Response(200, ['Content-Type' => 'application/json'], '{"hello": "world"}'),
            new Response(204, [], ''),
            new Response(400, [], ''),
        ];

        foreach ($statuses as $status) {
            $this->guzzleHandler->append($status);

            $expected = $this->http->get('https://somewhere.com/');
            self::assertSame($expected->getStatusCode(), $this->http->getLastStatusCode());
        }
    }

    /** @testdox Implements Guzzle's PSR-18 ClientInterface interface. **/
    public function testImplementsGuzzlesClientInterface()
    {
        self::assertInstanceOf(\GuzzleHttp\ClientInterface::class, $this->http);
    }

    public function testSupportsLoggingAllRequestsWithCuzzle()
    {
        // This actually tests whether RESTSpeaker works without the cuzzle package...

        $expectedBody = '<html lang="us">Hi</html>';
        $expected = new Response(200, ['Content-Type' => 'text/html'], $expectedBody);
        $this->guzzleHandler->append(
            $expected
        );

        // Disable cuzzle logging:
        $this->http->enableCuzzle = false;
        $actual = $this->http->get('https://somewhere.com/');
        self::assertEquals($expected, $actual);
        self::assertEquals($expectedBody, $actual->getBody());    }
}
