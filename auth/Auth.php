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
    protected int $driverPort = 9515;
    protected string $driverHost = 'http://localhost';
    protected RemoteWebDriver $driver;
    protected string $chromeDriverPath;

    public function __construct()
    {
        $this->detectChromeDriver();
    }

    /**
     * Detect and set the ChromeDriver path
     */
    protected function detectChromeDriver(): void
    {
        $os = PHP_OS_FAMILY;
        $architecture = php_uname('m');
        $basePath = __DIR__ . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR;
        switch ($os) {
            case 'Windows':
                if (PHP_INT_SIZE === 8) {
                    $driverDir = 'chromedriver-win64';
                } else {
                    $driverDir = 'chromedriver-win32';
                }
                $driverPath = $basePath . $driverDir . DIRECTORY_SEPARATOR . 'chromedriver.exe';
                break;

            case 'Linux':
                $driverDir = 'chromedriver-linux64';
                $driverPath = $basePath . $driverDir . DIRECTORY_SEPARATOR . 'chromedriver';
                break;

            case 'Darwin':
                // Apple Silicon (M1/M2) uses 'arm64'
                if (strpos($architecture, 'arm') !== false) {
                    $driverDir = 'chromedriver-mac-arm64';
                } else {
                    $driverDir = 'chromedriver-mac-x64';
                }
                $driverPath = $basePath . $driverDir . DIRECTORY_SEPARATOR . 'chromedriver';
                break;

            default:
                throw new \RuntimeException("Unsupported operating system: $os");
        }

        if (!file_exists($driverPath)) {
            throw new \RuntimeException("ChromeDriver not found for $os ($architecture) at expected path: $driverPath");
        }
        $this->chromeDriverPath = $driverPath;
    }

    /**
     * Start the WebDriver with Chrome options
     */
    protected function startDriver(): void
    {
        $nullDevice = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'NUL' : '/dev/null';
        $descriptorSpec = [
            0 => ["pipe", "r"],
            1 => ["file", $nullDevice, "a"],
            2 => ["file", $nullDevice, "a"]
        ];
        $process = proc_open("{$this->chromeDriverPath} --port={$this->driverPort}", $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            exit("Failed to start ChromeDriver\n");
        }
        $driverUrl = $this->driverHost . ':' . $this->driverPort;
        // Wait until ChromeDriver is ready
        $tries = 0;
        while ($tries < 10) {
            if (@file_get_contents("{$driverUrl}/status")) {
                break;
            }
            sleep(1);
            $tries++;
        }
        if ($tries >= 10) {
            proc_terminate($process);
            throw new Exception("ChromeDriver did not start in time.\n");
        }
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
     * Log in to Instagram and get auth cookies
     */
    public function login(string $username, string $password): string
    {
        $this->startDriver();
        $this->driver->get('https://www.instagram.com/accounts/login/');
        sleep(5);
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
