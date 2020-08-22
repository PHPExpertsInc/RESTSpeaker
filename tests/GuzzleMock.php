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

namespace PHPExperts\RESTSpeaker\Tests;

use GuzzleHttp\Client as GuzzleClient;
use LogicException;

class GuzzleMock extends GuzzleClient
{
    /** @var mixed */
    protected $response;

    public function setResponse($response)
    {
        $this->response = $response;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function request($method, $uri = '', array $options = [])
    {
        if ($this->response === null) {
            throw new LogicException('You need to set the Guzzle Mock\'s response.');
        }

        return $this->response;
    }
}
