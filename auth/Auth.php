<?php

namespace InstaAuth;

use Exception;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Cookie;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class Auth
{
    protected static int $driverPort = 9515;
    protected static $driverHost = 'http://localhost';
    protected RemoteWebDriver $driver;
    protected string $chromeDriverPath;

    // Constructor now accepts host and port parameters
    public function __construct(string $host = 'http://localhost', int $port = 9515)
    {
        self::$driverHost = $host;
        self::$driverPort = $port;
    }

    /**
     * Static method to set up the host and port for the driver
     */
    public static function setup(string $host, int $port): void
    {
        self::$driverHost = $host;
        self::$driverPort = $port;
    }

    /**
     * Connect to the running ChromeDriver instead of starting it
     */
    protected function connectDriver(): void
    {
        $driverUrl = self::$driverHost . ':' . self::$driverPort;

        // Check if ChromeDriver is running on the provided host and port
        if (!$this->isChromeDriverRunning($driverUrl)) {
            throw new Exception("ChromeDriver not running on $driverUrl. Kindly start the ChromeDriver.");
        }

        // Connect to the running ChromeDriver
        $options = new ChromeOptions();
        $options->addArguments([
            '--headless',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--window-size=1920,1080',
            '--disable-software-rasterizer',
            '--remote-debugging-port=9222',
            '--disable-extensions',
            '--disable-setuid-sandbox',
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        $this->driver = RemoteWebDriver::create($driverUrl, $capabilities, 5000);
    }

    /**
     * Check if ChromeDriver is running by checking the status on the provided URL
     */
    private function isChromeDriverRunning(string $url): bool
    {
        $statusUrl = $url . '/status';
        $response = @file_get_contents($statusUrl);

        // If we can get a response, ChromeDriver is running
        return !empty($response);
    }

    /**
     * Log in to Instagram and get auth cookies
     */
    public function login(string $username, string $password): string
    {
        $this->connectDriver();
        $this->driver->get('https://www.instagram.com/accounts/login/');
        $this->driver->wait(10)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('#loginForm')
            )
        );
        $form = $this->driver->findElement(WebDriverBy::id('loginForm'));
        $usernameField = $form->findElement(WebDriverBy::name('username'));
        $usernameField->sendKeys($username);
        // Locate and fill in the password field by its name
        $passwordField = $form->findElement(WebDriverBy::name('password'));
        $passwordField->sendKeys($password);

        // Submit the form by clicking the submit button
        $submitButton = $form->findElement(WebDriverBy::cssSelector('button[type="submit"]'));
        $submitButton->click();

        // Wait for login to complete
        sleep(10);
        $cookies = $this->driver->manage()->getCookies();
        $this->driver->quit();
        $decodedCookies = array_map(function (Cookie $cookie) {
            return $cookie->toArray();
        }, $cookies);
        $cookiedata = [];
        foreach ($decodedCookies as $cookie) {
            $cookiedata[$cookie['name']] = $cookie['value'];
        }
        return self::generateCookieAuth($cookiedata);
    }

    /**
     * Generate a serialized cookie string for the scrapper
     * @param array $cookies
     * @return string
     */
    public static function generateCookieAuth(array $cookies): string
    {
        return base64_encode(json_encode($cookies));
    }
}
