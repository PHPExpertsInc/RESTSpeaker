<?php declare(strict_types=1);

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

    public function setUp()
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
}
