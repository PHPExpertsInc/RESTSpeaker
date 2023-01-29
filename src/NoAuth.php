<?php declare(strict_types=1);

/**
 * This file is part of RESTSpeaker, a PHP Experts, Inc., Project.
 *
 * Copyright © 2019-2023 PHP Experts, Inc.
 * Author: Theodore R. Smith <theodore@phpexperts.pro>
 *  GPG Fingerprint: 4BF8 2613 1C34 87AC D28F  2AD8 EB24 A91D D612 5690
 *  https://www.phpexperts.pro/
 *  https://github.com/phpexpertsinc/RESTSpeaker
 *
 * This file is licensed under the MIT License.
 */

namespace PHPExperts\RESTSpeaker;

class NoAuth extends RESTAuth
{
    public function __construct(RESTSpeaker $apiClient = null)
    {
        parent::__construct(self::AUTH_NONE, $apiClient);
    }

    protected function generateOAuth2TokenOptions(): array
    {
    }

    protected function generatePasskeyOptions(): array
    {
    }
}
