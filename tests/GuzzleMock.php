<?php /** @noinspection PhpMissingParentCallCommonInspection */
declare(strict_types=1);

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

    public function request($method, $uri = '', array $options = [])
    {
        if ($this->response === null) {
            throw new LogicException('You need to set the Guzzle Mock\'s response.');
        }

        return $this->response;
    }
}
