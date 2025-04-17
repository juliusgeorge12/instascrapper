<?php

namespace InstaScrapper\Exception;

class ScrapperErrorException extends \Exception
{
    protected $message = 'Scrapper Error';
    protected $code = 500;
    protected $statusCode = 500;
    protected $response = [];

    public function __construct($message, $code, $statusCode, $response)
    {
        parent::__construct($message, $code);
        $this->statusCode = $statusCode;
        $this->response = $response;
    }
    public function statusCode()
    {
        return $this->statusCode;
    }
    public function response(): array | string
    {
        return $this->response;
    }
}
