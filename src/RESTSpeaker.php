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

namespace PHPExperts\RESTSpeaker;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * @mixin GuzzleClient
 * @method ResponseInterface|object|null get(string|UriInterface $uri, array $options = [])
 * @method ResponseInterface             head(string|UriInterface $uri, array $options = [])
 * @method ResponseInterface|object|null delete(string|UriInterface $uri, array $options = [])
 * @method PromiseInterface              getAsync(string|UriInterface $uri, array $options = [])
 * @method PromiseInterface              headAsync(string|UriInterface $uri, array $options = [])
 * @method PromiseInterface              putAsync(string|UriInterface $uri, array $options = [])
 * @method PromiseInterface              postAsync(string|UriInterface $uri, array $options = [])
 * @method PromiseInterface              patchAsync(string|UriInterface $uri, array $options = [])
 * @method PromiseInterface              deleteAsync(string|UriInterface $uri, array $options = [])
*/
class RESTSpeaker implements ClientInterface
{
    /** @var HTTPSpeaker Use this when you need the raw GuzzleHTTP. */
    public $http;

    /** @var RESTAuth */
    protected $authStrat;

    /** @var Response */
    protected $lastResponse;

    public function __construct(RESTAuth $authStrat, string $baseURI = '', HTTPSpeaker $http = null)
    {
        $this->authStrat = $authStrat;

        if (!$http) {
            $http = new HTTPSpeaker($baseURI);
        }
        $this->http = $http;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        // Literally any method name is callable in Guzzle, so there's no need to check is_callable().
        // Automagically inject auth headers into the RESTful methods.
        $restOptions = $this->authStrat->generateGuzzleAuthOptions();
        $arguments = $this->http->mergeGuzzleOptions($arguments, [$restOptions]);
        $arguments[1]['headers']['Content-Type'] = 'application/json';

        $response = $this->http->$name(...$arguments);
        $this->lastResponse = $response;

        if ($response instanceof Response) {
            // If empty, bail.
            $responseData = (string) $response->getBody();
            if (empty($responseData)) {
                return null;
            }

            // Attempt to decode JSON, if that's what we got.
            $decoded = json_decode($responseData);
            if (!empty($decoded)) {
                return $decoded;
            }
        }

        // Nothing worked out, so let's return the raw string.
        return $responseData;
    }

    /**
     * @param string              $method
     * @param string|UriInterface $uri
     * @param mixed               $body
     * @param array               $options
     * @return ResponseInterface|object|null
     */
    protected function callWithBody(string $method, $uri, $body, array $options = [])
    {
        if ($body !== null) {
            $options['json'] = $body;
        }

        return $this->__call($method, [$uri, $options]);
    }

    /**
     * @param string|UriInterface $uri
     * @param array|object|null   $body
     * @param array               $options
     * @return ResponseInterface|object|null
     */
    public function put($uri, $body = null, array $options = [])
    {
        return $this->callWithBody('put', $uri, $body, $options);
    }

    /**
     * @param string|UriInterface $uri
     * @param array|object|null   $body
     * @param array               $options
     * @return ResponseInterface|object|null
     */
    public function post($uri, $body = null, array $options = [])
    {
        return $this->callWithBody('post', $uri, $body, $options);
    }

    /**
     * @param string|UriInterface $uri
     * @param array|object|null   $body
     * @param array               $options
     * @return ResponseInterface|object|null
     */
    public function patch($uri, $body = null, array $options = [])
    {
        return $this->callWithBody('patch', $uri, $body, $options);
    }

    public function getLastResponse(): ?Response
    {
        return $this->lastResponse;
    }

    public function getLastStatusCode(): int
    {
        return $this->http->getLastStatusCode();
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

    public function getAuthStrat(): RESTAuthDriver
    {
        return $this->authStrat;
    }
}
