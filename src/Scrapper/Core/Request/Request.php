<?php

namespace InstaScrapper\Scrapper\Core\Request;

use InstaScrapper\Client\Client;

/**
 * Request class to handle requests
 * @package InstaScrapper\Scrapper\Core\Request
 * @author Julius George <julius.business12@gmail.com>
 */
class Request
{
    /**
     * The URL to send the request to
     * @var string
     */
    protected string $url;

    /**
     * The method to use for the request
     * @var string
     */
    protected string $method;
    /**
     * response status code
     * @var int
     */
    protected int $statusCode = 0;

    /**
     * response body
     * @var string
     */
    protected string $body = '';
    /**
     * response headers
     * @var array
     */
    protected array $responseHeaders = [];
    /**
     * data to send with the request
     * @var array
     */
    protected array $data = [];
    /**
     * Cookies to send with the request
     * @var array
     */
    protected array $cookies = [];
    /**
     * client to use for the request
     * @var \InstaScrapper\Client\Client
     */
    protected \InstaScrapper\Client\Client $client;
    /**
     * Laravel IoC container
     * @var \Illuminate\Container\Container
     */
    protected \Illuminate\Container\Container $container;
    /**
     * Headers to send with the request
     * @var array
     */
    protected array $headers = [];
    /**
     * Request constructor.
     * @param \Illuminate\Container\Container $container
     * @param \InstaScrapper\Client\Client $client
     */
    public function __construct(\Illuminate\Container\Container $container, \InstaScrapper\Client\Client $client)
    {
        $this->container = $container;
        $this->client = $client;
        $this->init();
    }
    protected function init()
    {
        Client::useCookie();
        $this->client->init();
    }
    /**
     * Set the URL for the request
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }
    /**
     * Set the method for the request
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method): self
    {
        $validMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
        if (!in_array(strtoupper($method), $validMethods)) {
            throw new \InvalidArgumentException('Invalid HTTP method: ' . $method);
        }
        $this->method = strtoupper($method);
        return $this;
    }
    /**
     * set the client to use json for sending request dat
     * @param bool $useJson
     * @return $this
     */
    public function useJson(bool $useJson = true): self
    {
        if ($useJson) {
            $this->headers['Content-Type'] = 'application/json';
        } else {
            unset($this->headers['Content-Type']);
        }
        return $this;
    }
    /**
     * Set the data for the request
     * @param array $data
     * @return $this
     */
    public function useData(array $data): self
    {
        $this->data = $data;
        return $this;
    }
    /**
     * update the data for the request
     * @param array $data
     * @return $this
     */
    public function updateData(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }
    /**
     * Set the cookies for the request
     * @param array $cookies
     * @return $this
     */
    public function useCookies(array $cookies): self
    {
        $this->cookies = $cookies;
        return $this;
    }
    /**
     * Set the headers for the request
     * @param array $headers
     * @return $this
     */
    public function useHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }
    /**
     * get the response status code
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    /**
     * get the response body
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }
    /**
     * get the response headers
     * @return array
     */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }
    /**
     * call the endpoint
     * @return string|array
     */
    public function call(): string|array
    {
        $this->client->setHeaders($this->headers);
        $this->client->setParams($this->data);
        $this->addCookies();
        $this->client->request($this->method, $this->url);
        $this->statusCode = $this->client->statusCode();
        $this->body = $this->client->responseBody();
        $this->responseHeaders = $this->client->getHeaders();
        if ($this->client->json()) {
            return $this->client->json();
        }
        return $this->client->responseBody();
    }
    protected function addCookies()
    {
        if ($this->cookies) {
            foreach ($this->cookies as $cookie) {
                $this->addCookie($cookie);
            }
        }
    }
    protected function addCookie(array $cookie)
    {
        if (!isset($cookie['name']) || !isset($cookie['value'])) {
            return;
        }
        $this->client->setCookie(
            $cookie['name'],
            $cookie['value'],
            isset($cookie['expiry']) ? $cookie['expiry'] : 0,
            isset($cookie['domain']) ? $cookie['domain'] : '.instagram.com',
            isset($cookie['path']) ? $cookie['path'] : '/',
            isset($cookie['secure']) ? $cookie['secure'] : true,
            isset($cookie['httpOnly']) ? $cookie['httpOnly'] : true
        );
    }
}
