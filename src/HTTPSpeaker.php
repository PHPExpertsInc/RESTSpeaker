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

namespace PHPExperts\RESTSpeaker;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\ClientInterface as iGuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
//use Namshi\Cuzzle\Middleware\CurlFormatterMiddleware;
use Namshi\Cuzzle\Middleware\CurlFormatterMiddleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @mixin GuzzleClient
 */
class HTTPSpeaker implements ClientInterface
{
    /** @var iGuzzleClient|GuzzleClient */
    protected $http;

    /** @var string */
    protected $mimeType = 'text/html';

    /** @var Response|null */
    protected $lastResponse;

    /** @var HandlerStack */
    public $guzzleMiddlewareStack;

    /** @var TestHandler */
    public $testHandler;

    public $enableCuzzle = true;

    public function __construct(string $baseURI = '', iGuzzleClient $guzzle = null)
    {
        $this->guzzleMiddlewareStack = HandlerStack::create();;
        if ($this->enableCuzzle && class_exists(CurlFormatterMiddleware::class)) {
            $testHandler = new TestHandler();

            $logger = new Logger('guzzle.to.curl');
            $logger->pushHandler($testHandler);

            $this->guzzleMiddlewareStack->after('cookies', new CurlFormatterMiddleware($logger)); //add the cURL formatter middleware
            $this->testHandler = $testHandler;
        }

        if (!$guzzle) {
            $guzzle = new GuzzleClient([
                'base_uri' => $baseURI,
                'handler' => $this->guzzleMiddlewareStack,
                'version' => '2.0',
            ]);
        }
        $this->http = $guzzle;
    }

    public function mergeGuzzleOptions(array $methodArgs, array $guzzleAuthOptions): array
    {
        $userOptions = $methodArgs[1] ?? [];
        $phpV = phpversion();

        if (!isset($methodArgs[1]['headers'])) {
            $methodArgs[1]['headers'] = [];
        }

        // @todo: Figure out how to include a real version number.
        $userOptions['headers']['User-Agent'] = $methodArgs[1]['headers']['User-Agent'] ?? "PHPExperts/RESTSpeaker-2.4 (PHP {$phpV})";
        $userOptions['headers']['Content-Type'] = $methodArgs[1]['headers']['Content-Type'] ?? $this->mimeType;

        $options = array_merge_recursive(
            $userOptions,
            ...$guzzleAuthOptions
        );

        $methodArgs[1] = $options;

        return $methodArgs;
    }

    public function getLastResponse(): ?Response
    {
        return $this->lastResponse;
    }

    public function getLastStatusCode(): int
    {
        // if lastResponse === null -> true
        if (!($this->lastResponse instanceof Response)) {
            return -1;
        }

        return $this->lastResponse->getStatusCode();
    }

    /**
     * Uses the Composition Pattern with Guzzle.
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $arguments = $this->mergeGuzzleOptions($arguments, []);

        // Literally any method name is callable in Guzzle, so there's no need to check.
        $this->lastResponse = $this->http->$name(...$arguments);

        return $this->lastResponse;
    }

    // BEGIN ClientInterface marshals.
    /** {@inheritDoc} */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->http->send($request, $options);
    }

    /** {@inheritDoc} */
    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return $this->http->sendAsync($request, $options);
    }

    /** {@inheritDoc} */
    public function request($method, $uri = '', array $options = []): ResponseInterface
    {
        return $this->http->request($method, $uri, $options);
    }

    /** {@inheritDoc} */
    public function requestAsync($method, $uri = '', array $options = []): PromiseInterface
    {
        return $this->http->requestAsync($method, $uri, $options);
    }

    /** {@inheritDoc} */
    public function getConfig($option = null)
    {
        return $this->http->getConfig();
    }
}
