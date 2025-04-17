<?php

namespace InstaScrapper\Platform;

use Exception;
use InstaScrapper\Platform\Concern\Platform;

class BasePlatform implements Platform
{
    /**
     * the default environment
     * @var string
     */
    protected const DEFAULT_ENVIRONMENT = '"Windows"';

    /**
     * the default environment build
     * @var string
     */
    protected const DEFAULT_ENVIRONMENT_BUILD = '"10.0.0"';

    /**
     * The browser security prefix
     * @var string
     */
    protected const SEC_PREFIX = 'Sec-';

    /**
     * the platform name
     * @var string $name
     */
    protected $name = 'Chrome';

    /**
     * the environment it is running on e.g windows
     * @var string $environment
     */
    protected $environment = 'Windows';

    /**
     * the environment version
     * @var string $environmentVersion
     */
    protected $environmentVersion = '"10.0.0"';

    /**
     *
     * @var string $version
     */
    protected $version = '1.0';

    /**
     * @var string $userAgent
     */
    protected $userAgent = '';

    /**
     * client ua
     * @var string
     */
    protected $ua = '';

    /**
     * set if the platform is a mobile device
     *
     * @var bool
     */
    protected $isMobile = false;


    /**
     * the generated headers
     * @var array|null
     */
    protected $generatedHeaders =  null;


    public function __construct()
    {
        $this->setEnvironment();
    }

    /**
     * set the headers
     * @return void
     */
    public function setHeaders(): void
    {
        $this->generateHeaders();
    }

    /**
     * get the platform's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * get the platform version
     *
     * @return @string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * get the platform running environment
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * set the environment
     *
     */
    protected function setEnvironment()
    {
        if (empty($this->environment) || is_null($this->environment)) {
            $this->environment = self::DEFAULT_ENVIRONMENT;
        }

        if (empty($this->environmentVersion) || is_null($this->environmentVersion)) {
            $this->environmentVersion = self::DEFAULT_ENVIRONMENT_BUILD;
        }
    }

    /**
     * get the headers
     *
     * @return array
     */
    public function getPlatformHeaders(): array
    {
        if (is_null($this->generatedHeaders)) {
            throw new Exception("Real Browser micmicking headers has not been set");
        }
        return $this->generatedHeaders;
    }

    /**
     * set the platform to be a desktop
     * @return void
     */
    public function desktop()
    {
        $this->isMobile = false;
    }

    /**
     * set the platform to be a mobile device
     * @return void
     */
    public function mobile()
    {
        $this->isMobile = true;
    }

    protected function generateHeaders()
    {
        $generatedHeaders = [
            self::SEC_PREFIX . 'Fetch-Dest' => 'document',
            self::SEC_PREFIX . 'Fetch-Mode' => 'navigate',
            self::SEC_PREFIX . 'Fetch-Site' => 'same-origin',
            self::SEC_PREFIX . 'Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => 1,
            'User-Agent' => $this->userAgent
        ];
        $generatedHeaders = array_merge($generatedHeaders, $this->generateClientHints());
        $this->generatedHeaders = $generatedHeaders;
    }

    /**
     * generate the client Hint headers
     * @return array
     */
    protected function generateClientHints()
    {
        $mobile = $this->isMobile ? '?1' : '?0';
        $ua_platform = $this->environment;
        $ua_platform_version = $this->environmentVersion;
        return [
            self::SEC_PREFIX . 'Ch-Ua-Mobile' => $mobile,
            self::SEC_PREFIX . 'Ch-Ua-Platform' => $ua_platform,
            self::SEC_PREFIX . 'Ch-Ua-Platform-Version' => $ua_platform_version,
            self::SEC_PREFIX . 'CH-Ua' => $this->ua
        ];
    }
}
