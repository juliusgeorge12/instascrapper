<?php

namespace InstaScrapper\Exception;

use Exception;

class NotSupportedPlatformException extends Exception
{
    public function __construct($platform)
    {
        parent::__construct("there is no support for [$platform] yet");
    }
}
