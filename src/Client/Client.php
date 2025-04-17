<?php

namespace InstaScrapper\Client;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use InstaScrapper\Exception\ClientNotInitiated;
use Psr\Http\Message\ResponseInterface;

/**
 * the http request handler
 *
 */
class Client
{
    /**
     * platform
     * @var \InstaScrapper\Platform\Concern\Platform $platform
     */
    protected $platform = null;

    /**
     * platform factory
     * @var \InstaScrapper\Platform\PlatformFactory $platformFactory
     */
    protected $platformFactory = null;

    /**
     * Http client
     * @var \GuzzleHttp\Client
     */
    protected $client = null;

    /**
     * the IOC container
     * @var \Illuminate\Container\Container
     */
    protected $app = null;

    /**
     * the http headers to add to the request
     * @var array $headers
     */
    protected $headers = null;

    /**
     * the request data to be sent with the request
     *
     * @var null|array $requestBody
     */
    protected $requestBody = null;

    /**
     * the response status code
     *
     * @var int $responseStatus
     */
    protected $responseStatus = null;

    /**
     * the response body
     *
     * @var string $responseBody
     */
    protected $responseBody = null;

    /**
     * the response headers
     * @var array $responseHeaders
     */
    protected $responseHeaders = null;

    /**
     * the json decoded response
     * @var null|array $jsonDecodedResponse
     */
    protected $jsonDecodedResponse = null;

    /**
     * set the request to be a json request
     * @var bool
     */
    protected $isJson = false;

    /**
     * @var bool $useCookie
     */
    protected static $useCookie = false;

    /**
     * hold user manually set cookie
     * @var array $cookies
     */
    protected $cookies = [];

    /**
     * @var array $proxyCredentails
     */
    protected static $proxyCredentails = [];

    /**
     * @var bool $useProxy
     */
    protected static $useProxy = false;

    /**
     * the certifcation path
     * @var string $certPath
     */
    protected static $certPath = '';

    /**
     * debug mode
     * @var bool $debug
     */
    protected static $debug = false;

    public function __construct(
        \Illuminate\Container\Container $app,
        \InstaScrapper\Platform\PlatformFactory $platformFactory
    ) {
        $this->app = $app;
        $this->platformFactory = $platformFactory;
        $this->client = $app->make(GuzzleHttpClient::class);
    }

    /**
     * init the client
     *
     * @param string $platformName
     * @return void
     */
    public function init(string $platformName = 'chrome')
    {
        $this->platform = $this->platformFactory->getPlatform($platformName);
        self::$certPath = __DIR__ . '/Cert/cacert.pem';
        $this->initHeaders();
    }

    /**
     * set the debug mode
     */
    public static function debug(bool $debug = true)
    {
        self::$debug = $debug;
    }

    /**
     * get the platform headers and add it to the request
     *
     * @return void
     */
    protected function initHeaders()
    {
        $this->platform->setHeaders();
        $this->headers = $this->platform->getPlatFormHeaders();
        $this->headers = array_merge($this->headers, $this->getDefaultHeaders());
    }

    /**
     * set the client to use cookie
     * @param bool $use
     */
    public static function useCookie(bool $use = true)
    {
        self::$useCookie = $use;
    }

    /**
     * set the client to use proxy
     * @param bool $use
     */
    public static function useProxy(bool $use = true)
    {
        self::$useProxy = $use;
    }

    /**
     * set the proxy credentials
     * @param string $server
     * @param string|int $port
     * @param string $password
     * @param string $username
     * @param bool $secure
     * @return void
     */
    public static function setupProxy(
        string $server,
        string|int $port,
        string $password,
        string $username,
        bool $secure = true
    ) {
        self::$proxyCredentails = [
            "password" => $password,
            "username" => $username,
            "server" => $server,
            "port" => $port,
            "secure" => $secure
        ];
    }

    /**
     * set the request to be a json request
     * @param bool $isJson
     */
    public function useJson(bool $isJson = true)
    {
        $this->isJson = $isJson;
    }

    /**
     * set the request headers
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**#
     * get the default headers
     * @return array
     */
    public function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Encoding' => 'gzip, deflate',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Priority' => 'u=0, i',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
        ];
    }
    /**
     * sends an http request
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @return \InstaScrapper\Client\Client
     */
    public function request(string $method = 'GET', $url = '', $headers = [])
    {
        if (is_null($this->platform)) {
            throw new ClientNotInitiated();
        }
        if (self::$useCookie) {
            $this->headers['Cookie'] = $this->generateCookieString();
        }
        $options = [
            'headers' => $this->headers,
            'verify' => self::$certPath
        ];
        if (!is_null($this->requestBody) || !empty($this->requestBody)) {
            if ($this->isJson) {
                $options["body"] = json_encode($this->requestBody);
            } else {
                $options["form_params"] = $this->requestBody;
            }
        }
        if (self::$debug) {
            $options["debug"] = true;
        }
        if (self::$useProxy) {
            $password = isset(self::$proxyCredentails["password"]) ?
                self::$proxyCredentails["password"] : '';
            $username = isset(self::$proxyCredentails["username"]) ?
                self::$proxyCredentails["username"] : '';
            $port = isset(self::$proxyCredentails["port"]) ?
                self::$proxyCredentails["port"] : '';
            $server = isset(self::$proxyCredentails["server"]) ?
                self::$proxyCredentails["server"] : '';
            $secure = isset(self::$proxyCredentails["secure"]) ?
                (bool) self::$proxyCredentails["secure"] : true;
            $scheme = $secure ? "https://" : "http://";
            $proxy = $scheme . "$username:$password@$server:$port";
            $options["proxy"] = $proxy;
        }
        try {
            $this->handleResponse($this->client->request($method, $url, $options));
        } catch (ClientException $e) {
            $this->handleResponse($e->getResponse());
        }
        if ($this->isJsonResponse()) {
            $this->jsonDecodedResponse = json_decode($this->responseBody, true);
        }
        return $this;
    }

    private function handleResponse(ResponseInterface $response)
    {
        $this->responseHeaders = $response->getHeaders();
        $this->responseStatus = $response->getStatusCode();
        $this->responseBody = $response->getBody()->getContents();
        if (self::$useCookie) {
            $this->storeCookies();
        }
    }

    /**
     * store the cookies from the response if useCookie is set
     */
    private function storeCookies()
    {
        if (self::$useCookie) {
            $cookies = $this->responseHeaders['Set-Cookie'] ?? [];
            if (is_array($cookies)) {
                foreach ($cookies as $cookie) {
                    $this->cookies[] = $this->parseCookie($cookie);
                }
            }
        }
    }
    /**
     * parse the cookie string
     * @param string $cookieString
     * @return array
     */
    private function parseCookie(string $cookieString): array
    {
        $parts = array_map('trim', explode(';', $cookieString));
        $cookie = [];
        // First part contains name=value
        if (!empty($parts)) {
            [$name, $value] = explode('=', array_shift($parts), 2);
            $cookie['name'] = $name;
            $cookie['value'] = trim($value, '"');
        }
        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                [$key, $val] = explode('=', $part, 2);
                $cookie[strtolower($key)] = trim($val, '"');
            } else {
                // Flags like Secure, HttpOnly without values
                $cookie[strtolower($part)] = true;
            }
        }
        $cookie['expiry'] = isset($cookie['expires']) ? strtotime($cookie['expires']) : null;
        unset($cookie['expires']);
        return $cookie;
    }

    /**
     * generate the cookie string
     * @return string
     */
    private function generateCookieString(): string
    {
        $cookiePairs = [];

        foreach ($this->cookies as $cookie) {
            // Skip expired cookies
            if (isset($cookie['expiry']) && $cookie['expiry'] < time()) {
                continue;
            }
            // Optional: domain/path checks can be added here too
            $cookiePairs[] = "{$cookie['name']}={$cookie['value']}";
        }
        return implode(';', $cookiePairs);
    }
    /**
     * check if the response is a json response
     *
     * @return bool
     */
    private function isJsonResponse(): bool
    {
        $contentType = $this->responseHeaders['Content-Type'] ?? '';
        if (is_array($contentType)) {
            $contentType = implode(';', $contentType);
        }
        return stripos($contentType, 'application/') === 0 && stripos($contentType, 'json') !== false;
    }
    public function getHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * set the request body|params
     *
     * @param array $params
     */
    public function setParams(array $params)
    {
        $this->requestBody = $params;
    }

    /**
     * get the response status code
     *
     * @return int
     */
    public function statusCode(): int
    {
        return (int) $this->responseStatus;
    }

    /**
     * get the response body
     *
     * @return string
     */
    public function responseBody(): string
    {
        return $this->responseBody;
    }

    /**
     * get the json decoded response body
     *
     * @return array
     */
    public function json(): array
    {
        return $this->jsonDecodedResponse ??  [];
    }

    /**
     * get the response text
     *
     * @return string
     */
    public function text(): string
    {
        return $this->responseBody;
    }

    /**
     * get the stored cookie
     * @param null|string $key
     * @return string|array
     */
    public function getCookie(string $key = '')
    {
        $cookieArray = $this->cookies;
        if (empty($key)) {
            return $cookieArray;
        }
        foreach ($cookieArray as $cookie) {
            if ($cookie['name'] === $key) {
                return $cookie['value'];
            }
        }
        return null;
    }

    /**
     * clear the cookie jar
     * @return void
     */
    public function clearCookie()
    {
        $this->cookies = [];
    }

    /**
     * set a cookie
     * @param string $name
     * @param string $value
     * @param int $expires
     * @param string $domain
     * @param string $path
     * @param bool $secure
     * @param bool $httpOnly
     * @param bool $hostOnly
     * @param string $sameSite
     * @return void
     */
    public function setCookie(
        string $name,
        string $value,
        int $expires = 0,
        string $domain = '',
        string $path = '/',
        bool $secure = false,
        bool $httpOnly = false,
        bool $hostOnly = false,
        string $sameSite = ''
    ) {
        $this->cookies[] = [
            'name' => $name,
            'value' => $value,
            'expires' => $expires,
            'domain' => $domain,
            'path' => $path,
            'secure' => $secure,
            'httpOnly' => $httpOnly,
            'hostOnly' => $hostOnly,
            'sameSite' => $sameSite
        ];
    }
}
