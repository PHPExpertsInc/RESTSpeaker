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
use GuzzleHttp\Psr7\Response;

/**
 * @mixin GuzzleClient
 */
final class RESTSpeaker
{
    /** @var HTTPSpeaker Use this when you need the raw GuzzleHTTP. */
    public $http;

    /** @var RESTAuth */
    protected $authStrat;

    /** @var string */
    protected $mimeType = 'application/json';

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

        // Nothing worked out, so let's return what we got.
        return $response;
    }

    public function getLastResponse(): ?Response
    {
        return $this->lastResponse;
    }

    public function getLastStatusCode(): int
    {
        return $this->http->getLastStatusCode();
    }
}
