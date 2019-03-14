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
final class RESTSpeaker extends HTTPSpeaker
{
    /** @var HTTPSpeaker Use this when you need the raw GuzzleHTTP. */
    public $http;

    /** @var RESTAuth */
    protected $authStrat;

    public function __construct(RESTAuth $authStrat, string $baseURI = '', HTTPSpeaker $http = null)
    {
        parent::__construct($baseURI);

        $this->authStrat = $authStrat;

        if (!$http) {
            $http = new HTTPSpeaker($baseURI);
        }
        $this->http = $http;
    }

    public function __call(string $name, array $arguments)
    {
        $mergeGuzzleHTTPOptions = function(array $methodArgs): array {
            $userOptions = $methodArgs[1] ?? [];
            $options = array_merge_recursive(
                $userOptions,
                [
                    'headers' => [
                        // @todo: Figure out how to include a real version number.
                        'User-Agent'   => 'PHPExperts/RESTSpeaker/1.0 (PHP 7)',
                        'Content-Type' => 'application/json',
                    ],
                ],
                $this->authStrat->generateGuzzleAuthOptions()
            );

            $methodArgs[1] = $options;

            return $methodArgs;
        };

        // Literally any method name is callable in Guzzle, so there's no need to check is_callable().
        // Automagically inject auth headers into the RESTful methods.
        if (in_array($name, ['get', 'post', 'put', 'patch', 'delete'])) {
            $arguments = $mergeGuzzleHTTPOptions($arguments);
        }

        $response = $this->http->$name(...$arguments);

        if ($response instanceof Response) {
            // If empty, bail.
            $responseData = $response->getBody()->getContents();
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
}