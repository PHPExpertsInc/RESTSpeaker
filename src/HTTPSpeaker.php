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

    /** @var Response */
    protected $lastResponse;

    public function __construct(string $baseURI = '', iGuzzleClient $guzzle = null)
    {
        if (!$guzzle) {
            $guzzle = new GuzzleClient(['base_uri' => $baseURI]);
        }
        $this->http = $guzzle;
    }

    public function mergeGuzzleOptions(array $methodArgs, array $guzzleAuthOptions): array
    {
        $userOptions = $methodArgs[1] ?? [];
        $phpV = phpversion();

        $options = array_merge_recursive(
            $userOptions,
 /*           [
                'headers' => [
                    // @todo: Figure out how to include a real version number.
                    'User-Agent'   => "PHPExperts/RESTSpeaker-1.0 (PHP {$phpV})",
                    'Content-Type' => $guzzleAuthOptions[0]['Content-Type'] ?? $this->mimeType,
                ],
	   ],*/
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
        if (!$this->lastResponse || !($this->lastResponse instanceof Response)) {
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
}
