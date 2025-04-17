<?php

namespace InstaScrapper\Config;

use Exception;

class Config
{
    /**
     * config path
     * @var string $configPath
     */
    protected static $configPath = null;

    /**
     * config file
     * @var string $configFile
     */
    protected static $configFile = 'config.json';

    /**
     * config file directory
     * @var string $configDir
     */
    protected static $configDir = '';

    /**
     * the config
     * @var array $config
     */
    protected static $config = [];

    /**
     * config variables
     * @var array $variables
     */
    protected $variables = [];

    /**
     * @param string $configPath
     */
    public function __construct(string $configPath = '')
    {
        self::$configPath = $configPath;
        $this->init();
    }

    /**
     * init the config
     *
     */
    private function init()
    {
        $this->addVariable('{root}', __DIR__);
        $this->initConfig();
        $this->load();
    }

    /**
     * check config
     * @return void
     */
    private function initConfig()
    {
        if (empty(self::$configDir)) {
            self::$configDir = __DIR__ . DIRECTORY_SEPARATOR;
        }

        if (substr(self::$configDir, strlen(self::$configDir) - 1) !== DIRECTORY_SEPARATOR) {
            self::$configDir .= DIRECTORY_SEPARATOR;
        }

        if (empty(self::$configPath)) {
            self::$configPath = self::$configDir . self::$configFile;
        }
    }

    /**
     * load the config file
     * @return void
     */
    private function load()
    {
        if (!file_exists($file = self::$configPath)) {
            throw new Exception("the config file [$file] is missing");
        }
        $configFileContent = file_get_contents(self::$configPath);
        if (empty($configFileContent)) {
            throw new Exception("the config file [$file] can not be empty");
        } elseif ($config = json_decode($configFileContent, true)) {
            self::$config = $config;
        } else {
            throw new Exception("the config file [$file] must be a valid json file");
        }
    }

    /**
     * add a variable to the config
     * @param string $name
     * @param string $value
     * @return void
     */
    public function addVariable(string $name, string $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * get the proxy username
     * @return string
     */
    public function getProxyUsername()
    {
        return $this->tap('proxy.username');
    }

    /**
     * get the proxy password
     * @return string
     */
    public function getProxyPassword()
    {
        return $this->tap('proxy.password');
    }

    /**
     * get the proxy server
     * @return string
     */
    public function getProxyServer()
    {
        return $this->tap('proxy.server');
    }

    /**
     * get the proxy port
     * @return int
     */
    public function getProxyPort()
    {
        return (int) $this->tap('proxy.port');
    }

    /**
     * get the proxy secure flag
     * @return bool
     */
    public function isSecureProxy()
    {
        return (bool) $this->tap('proxy.secure');
    }

    /**
     * should use proxy
     * @return bool
     */
    public function useProxy()
    {
        return (bool) $this->tap('useProxy', false);
    }

    /**
     * should use bot
     * @return bool
     */
    public function useBot()
    {
        return (bool) $this->tap('useBot', false);
    }

    /**
     * tap value from the config
     * @param string $key
     * @param  mixed $default
     * @return mixed
     */
    public function tap(string $key, mixed $default = '')
    {
        $parts = explode(".", $key);
        if (count($parts) <= 1) {
            return isset(self::$config[$key]) ? self::$config[$key] : $default;
        }
        $currentData = self::$config;
        foreach ($parts as $part) {
            if (is_array($currentData) && array_key_exists($part, $currentData)) {
                $currentData = $currentData[$part];
            } else {
                $currentData = $default;
                break;
            }
        }
        return $this->replaceVariables($currentData);
    }

    /**
     * replace variables in the string
     * @param string $string
     * @return string
     */
    private function replaceVariables(string $string)
    {
        $keys = array_keys($this->variables);
        $values = array_values($this->variables);
        return str_replace($keys, $values, $string);
    }
    /**
     * set the configPath
     * @param string $configPath absolute config path
     */
    public static function setConfigPath(string $configPath)
    {
        $file = basename($configPath);
        $dir = dirname($configPath);
        self::$configDir = $dir;
        self::$configFile = $file;
    }
}
