<?php

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
use GuzzleHttp\ClientInterface as iGuzzleClient;
use GuzzleHttp\Psr7\Response;

/**
 * @mixin GuzzleClient
 */
class HTTPSpeaker
{
    /** @var iGuzzleClient|GuzzleClient */
    protected $http;

    /** @var string */
    protected $mimeType = 'text/html';

    /** @var Response|null */
    protected $lastResponse;

    public function __construct($baseURI = '', iGuzzleClient $guzzle = null)
    {
        if (!$guzzle) {
            $guzzle = new GuzzleClient(['base_uri' => $baseURI]);
        }
        $this->http = $guzzle;
    }

    /**
     * @param array $methodArgs
     * @param array $guzzleAuthOptions
     * @return array
     */
    public function mergeGuzzleOptions(array $methodArgs, array $guzzleAuthOptions)
    {
        if (!isset($methodArgs[1])) {
            $methodArgs[1] = [];
        }
        $userOptions = $methodArgs[1];
        $phpV = phpversion();

        if (!isset($methodArgs[1]['headers'])) {
            $methodArgs[1]['headers'] = [];
        }

        // @todo: Figure out how to include a real version number.
        if (!isset($methodArgs[1]['headers']['User-Agent'])) {
            $methodArgs[1]['headers']['User-Agent'] = "PHPExperts/RESTSpeaker-1.0 (PHP {$phpV})";
        }
        $userOptions['headers']['User-Agent'] = $methodArgs[1]['headers']['User-Agent'];

        if (!isset($methodArgs[1]['headers']['Content-Type'])) {
            $methodArgs[1]['headers']['Content-Type'] = $this->mimeType;
        }
        $userOptions['headers']['Content-Type'] = $methodArgs[1]['headers']['Content-Type'];

        $options = array_merge_recursive(
            $userOptions,
            ...$guzzleAuthOptions
        );

        $methodArgs[1] = $options;

        return $methodArgs;
    }

    /**
     * @return Response|null
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * @return int
     */
    public function getLastStatusCode()
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
    public function __call($name, array $arguments)
    {
        $arguments = $this->mergeGuzzleOptions($arguments, []);

        // Literally any method name is callable in Guzzle, so there's no need to check.
        $this->lastResponse = $this->http->$name(...$arguments);

        return $this->lastResponse;
    }
}
