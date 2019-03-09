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

use Error;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use stdClass;

/**
 * @mixin GuzzleClient
 */
final class RESTSpeaker extends HTTPSpeaker
{
    /** @var RestAuth */
    protected $auth;

    public function __construct(RestAuth $auth, string $baseURI = '')
    {
        parent::__construct($baseURI);

        $this->auth = $auth;
    }

    public function __call($name, $arguments): ?stdClass
    {
        if (is_callable([$this->http, $name])) {
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

        $callName = self::class . '::' . $name;
        throw new Error("Invalid method: '{$callName}'.");
    }
}