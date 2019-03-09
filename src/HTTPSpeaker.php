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
use stdClass;

/**
 * @mixin GuzzleClient
 */
class HTTPSpeaker
{
    /** @var GuzzleClient */
    protected $http;

    public function __construct(string $baseURI = '')
    {
        $this->http = new GuzzleClient(['base_uri' => $baseURI]);
    }

    public function __call($name, $arguments)
    {
        if (is_callable([$this->http, $name])) {
            return $this->http->$name(...$arguments);
        }

        $callName = self::class . '::' . $name;
        throw new Error("Invalid method: '{$callName}'.");
    }
}
