<?php

namespace InstaScrapper\Exception;

use Exception;

class ClientNotInitiated extends Exception
{
    public function __construct()
    {
        parent::__construct('Client has not be initiated, call init method');
    }
}
