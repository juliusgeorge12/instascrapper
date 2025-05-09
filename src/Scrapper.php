<?php

namespace InstaScrapper;

use InstaScrapper\Exception\ScrapperErrorException;

/**
 * Scrapper class
 * @package InstaScrapper\Scrapper
 * @author Julius George <julius.business12@gmail.com>
 */
abstract class Scrapper
{
    /**
     * the time to sleep between requests when paginating
     * @var string
     */
    protected const SLEEPTIME = 10;
    /**
     * the endpoint to use for the request
     * @var string
     */
    protected ?string $endpoint = null;
    /**
     * the method to use for the request
     * @var string
     */
    protected ?string $method = null;
    /**
     * url variables
     * @var array
     */
    protected array $variables = [];
    /**
     * the data to pass to be used with the scrapper
     * @var array
     */
    protected array $with = [];
    /**
     * the headers to use for the request
     * @var array
     */
    protected array $headers = [];
    /**
     * the cookies to use for the request
     * @var array
     */
    /**
     * payload to pass with the request
     * @var array
     */
    protected array $payload = [];

    protected array $cookies = [];
    /**
     * auth cookies
     * @var array
     */
    protected static array $authCookies = [];
    /**
     * the required cookies names in the cookie auth
     * @var array
     */
    protected static array $requiredCookies = [
        'csrftoken',
        'sessionid',
        //'rur', maynot be required
        'mid',
        'ig_did',
        'ds_user_id',
    ];
    /**
     * check if cookie authentication is present
     * @var bool
     */
    protected static bool $authenticated = false;
    /**
     * the request handler
     * @var \InstaScrapper\Scrapper\Core\Request\Request
     */
    protected \InstaScrapper\Scrapper\Core\Request\Request $request;
    /**
     * laravel IoC container
     * @var \Illuminate\Container\Container
     */
    protected \Illuminate\Container\Container $container;
    /**
     * send the data as json
     * @var bool
     */
    protected bool $useJson = false;

    /**
     * the response data
     * @var mixed
     */
    protected mixed $response = null;
    /**
     * the response status code
     * @var int
     */
    protected int $statusCode = 0;

    public function __construct()
    {
        $this->resolveContainer();
        $this->resolveDependencies();
    }
    /**
     * resolve the container
     * @return void
     */
    protected function resolveContainer(): void
    {
        $this->container = new \Illuminate\Container\Container();
    }
    /**
     * resolve the dependencies
     * @return void
     */
    protected function resolveDependencies(): void
    {
        $this->request = $this->container->make(\InstaScrapper\Scrapper\Core\Request\Request::class);
    }
    /**
     * set the cookie auth
     * @param string serialized cookie auth string
     * @return void
     */
    public static function cookieAuth(string $cookieAuth): void
    {
        $cookieAuth  = json_decode(base64_decode($cookieAuth), true);
        if (is_null($cookieAuth) || !$cookieAuth || !is_array($cookieAuth)) {
            throw new \InvalidArgumentException('Invalid cookie auth');
        }
        $cookienames = array_keys($cookieAuth);
        foreach (self::$requiredCookies as $requiredCookie) {
            if (!in_array($requiredCookie, $cookienames)) {
                throw new \InvalidArgumentException('Invalid cookie auth: ' . $requiredCookie . ' not found');
            }
        }
        self::$authCookies = self::generateCookies($cookieAuth);
        self::$authenticated = true;
    }
    /**
     * generate a valid cookie from the auth cookie array name/value pair
     * @param array $cookies
     * @return array
     */
    protected static function generateCookies(array $cookies): array
    {
        $cookie = [];
        foreach ($cookies as $name => $value) {
            $cookie[] = [
                'name' => $name,
                'value' => $value,
                'expiry' => 0,
                'domain' => '.instagram.com',
                'path' => '/',
                'secure' => true,
                'httpOnly' => true
            ];
        }
        return $cookie;
    }
    /**
     * check if all requirements are met befrore making a request
     * @return void
     * @throws \Exception
     */
    protected function checkRequirements(): void
    {
        if (is_null($this->endpoint)) {
            throw new \Exception('Endpoint can not be null');
        }
        if (is_null($this->method)) {
            throw new \Exception('Method can not be null');
        }
        if (!self::$authenticated && empty(self::$authCookies)) {
            throw new \RuntimeException('Auth cookie not available kindly set the cookie auth');
        }
    }
    protected function getCsrfTokenFromCookie(): ?string
    {
        foreach ($this->cookies as $cookie) {
            if ($cookie['name'] === 'csrftoken') {
                return $cookie['value'];
            }
        }
        return null;
    }
    protected function getDefaultHeaders(): array
    {
        return [
            'X-Asbd-Id' => '359341',
            'X-CSRFToken' => $this->getCsrfTokenFromCookie(),
            'X-Ig-App-Id' => '936619743392459',
            'Origin' => 'https://www.instagram.com',
            'Referer' => 'https://www.instagram.com/'
        ];
    }
    protected function getEndpoint(): string
    {
        $variables = array_map(
            fn ($variableName) => ':' . $variableName,
            array_keys($this->variables)
        );
        $values = array_values($this->variables);
        return str_replace($variables, $values, $this->endpoint);
    }
    protected function send()
    {
        $this->checkRequirements();
        $this->cookies = array_merge($this->cookies, self::$authCookies);
        $this->headers = array_merge($this->headers, $this->getDefaultHeaders());
        $this->request->setUrl($this->getEndpoint());
        $this->request->setMethod($this->method);
        $this->request->useHeaders($this->headers);
        $this->request->useCookies($this->cookies);
        $this->request->useData($this->payload);
        $this->request->useJson($this->useJson);
        $response = $this->request->call();
        $this->statusCode = $this->request->getStatusCode();
        $this->response = $response;
        $this->handleResponse();
    }
    abstract public function scrape(): mixed;
    /**
     * make data available to the scrapper
     * @param array $data
     * @return $this
     */
    public function with(array $data): static
    {
        $this->with = $data;
        return $this;
    }
    protected function handleResponse()
    {
        if ($this->statusCode == 200 || $this->statusCode == 201) {
            if (is_array($this->response)) {
                if (isset($this->response['status']) && $this->response['status'] == 'ok') {
                    return;
                }
                if (isset($this->response['status']) && $this->response['status'] == 'fail') {
                    throw new ScrapperErrorException(
                        'Request failed',
                        $this->statusCode,
                        $this->statusCode,
                        $this->response
                    );
                }
                if (
                    !isset($this->response['status']) ||
                    ($this->response['status'] != 'ok' && $this->response['status'] != 'fail')
                ) {
                    throw new ScrapperErrorException(
                        'Unknown response encountered',
                        $this->statusCode,
                        $this->statusCode,
                        $this->response
                    );
                }
            } else {
                throw new ScrapperErrorException(
                    'Non json response encountered',
                    $this->statusCode,
                    $this->statusCode,
                    $this->response
                );
            }
        }
        if ($this->statusCode == 429) {
            throw new ScrapperErrorException(
                'Rate limit exceeded',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
        if ($this->statusCode == 403) {
            throw new ScrapperErrorException(
                'Forbidden',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
        if ($this->statusCode == 404) {
            throw new ScrapperErrorException(
                'Not found',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
        if ($this->statusCode == 500) {
            throw new ScrapperErrorException(
                'Internal server error',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
        if ($this->statusCode == 502) {
            throw new ScrapperErrorException(
                'Bad gateway',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
        if ($this->statusCode == 503) {
            throw new ScrapperErrorException(
                'Service unavailable',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
        if ($this->statusCode == 504) {
            throw new ScrapperErrorException(
                'Gateway timeout',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
        if ($this->statusCode == 401) {
            throw new ScrapperErrorException(
                'Unauthorized',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
        if ($this->statusCode == 400) {
            throw new ScrapperErrorException(
                'Bad request',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
        if ($this->statusCode == 408) {
            throw new ScrapperErrorException(
                'Request timeout',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
        if ($this->statusCode == 429) {
            throw new ScrapperErrorException(
                'Too many requests',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
        if ($this->statusCode == 422) {
            throw new ScrapperErrorException(
                'Unprocessable entity',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
        if ($this->statusCode == 408) {
            throw new ScrapperErrorException(
                'Request timeout',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
        if ($this->statusCode == 302) {
            throw new ScrapperErrorException(
                'Redirect detected, probably due to not being logged in',
                $this->statusCode,
                $this->statusCode,
                $this->response
            );
        }
    }
}
