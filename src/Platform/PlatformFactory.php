<?php

namespace InstaScrapper\Platform;

use InstaScrapper\Exception\NotSupportedPlatformException;

class PlatformFactory
{
    /**
     * list of supported platform
     * @var array
     */
    protected $supportPlatform = ['chrome'];

    /**
     * Ioc Container
     *
     * @var \Illuminate\Container\Container
     */
    protected $app = null;

    public function __construct(\Illuminate\Container\Container $app)
    {
        $this->app = $app;
    }

    /**
     * check if the platfom is a supported platfrom
     *
     * @param string $platform
     */
    protected function checkSupportedPlatform(string $platform)
    {
        if (!in_array($platform, $this->supportPlatform)) {
            throw new NotSupportedPlatformException($platform);
        }
        return true;
    }

    /**
     * get the platform
     * @param string $platform
     *
     * @return \InstaScrapper\Platform\Concern\Platform
     */
    public function getPlatform(string $platform)
    {
        $this->checkSupportedPlatform($platform);
        switch ($platform) {
            case 'chrome':
                return $this->app->make(Chrome::class);
            break;
            default:
                return $this->app->make(Chrome::class);
        }
    }
}
