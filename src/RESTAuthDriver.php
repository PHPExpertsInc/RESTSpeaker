<?php

namespace PHPExperts\RESTSpeaker;

interface RESTAuthDriver
{
    public function setApiClient(RESTSpeaker $apiClient);
    public function generateGuzzleAuthOptions();
}
