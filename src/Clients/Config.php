<?php
namespace Puleeno\Goader\Clients;

use Puleeno\Goader\Command;
use Puleeno\Goader\Encryption;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Logger;
use Puleeno\Goader\Transfomers\CookieJar as CookieJarTransformer;

class Config
{
    protected $supported = ['account', 'host', 'core'];
    protected $commands   = [];
    protected $options = [];
    protected $configIsLoaded = false;
    protected $configs = [];

    protected static $instance;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self('client');
        }
        return self::$instance;
    }

    public function __construct($commands)
    {
        if (empty($commands)) {
            exit('Please input your configs');
        }
        $this->commands = $commands;
        $this->options = Command::getCommand()->getOptions();
        if (!$this->configIsLoaded) {
            $this->getAllConfigs();
            $this->configIsLoaded = true;
        }
    }

    public function run()
    {
        $command = array_shift($this->commands);
        if (!in_array($command, $this->supported)) {
            exit(sprintf('Command %s is not supported', $command));
        }
        if (empty($this->commands)) {
            exit(sprintf('Please input your %s configs', $command));
        }
        $this->$command();
    }

    public function account()
    {
        $command = Command::getCommand();
        $host = $this->options['host']->getValue();
        if (!$this->validateHost($host)) {
            exit('The host has invalid format');
        }
        if (!$this->validateAccount($command[2], $command[3])) {
            exit(sprintf('Please input account for host %s', $host));
        }
        $optionFile = sprintf('%s/accounts.dat', Environment::getUserGoaderDir());
        $options = array();
        if (file_exists($optionFile)) {
            $options = unserialize(file_get_contents($optionFile));
        }
        $password = Encryption::encrypt($command[3]);
        $options[$host] = array(
            'account' => $command[2],
            'password' => $password,
        );
        $h = fopen($optionFile, 'w');
        fwrite($h, serialize($options));
        fclose($h);
    }

    protected function validateHost($host)
    {
        return is_int(strpos($host, '.'));
    }

    protected function validateAccount($account, $password)
    {
        if (empty($account) || empty($password)) {
            return false;
        }
        return true;
    }

    /**
     * List host configs
     *
     * - load-cookies: Convert cookies.txt from browser export to CookieJar
     */
    public function host()
    {
        $command = Command::getCommand();
        $name = $this->options['name']->getValue();
        if (!$this->validateHost($name)) {
            exit('The host name has invalid format');
        }
        $cookieJar = '';
        if (($cookiesTxt = $this->options['load-cookies']->getValue())) {
            if (!file_exists($cookiesTxt)) {
                Logger::log(sprintf('File %s is not exists', $cookiesTxt));
                exit;
            }
            $cookiesContent = file_get_contents($cookiesTxt);
            $transformer = new CookieJarTransformer($cookiesContent);
            $cookieJar = $transformer->convertToCookieJar();
        }
        if (($cookiejarFile = $this->options['load-cookiejar']->getValue())) {
            if (!file_exists($cookiejarFile)) {
                Logger::log(sprintf('File %s is not exists', $cookiejarFile));
                exit;
            }
            $cookiesContent = file_get_contents($cookiejarFile);
            if (!json_decode($cookiesContent)) {
                Logger::log('Your cookiejar is invalid');
                exit;
            }
            $cookieJar = $cookiesContent;
        }

        if (!empty($cookieJar)) {
            $cookieJarFile = sprintf('%s/hosts/%s.json', Environment::getUserGoaderDir(), 'lezhin.com');
            $h = fopen($cookieJarFile, 'w');
            fwrite($h, $cookieJar);
            fclose($h);
        }

        if (($token = $this->options['token']->getValue())) {
            $tokensFile = sprintf('%s/tokens.dat', Environment::getUserGoaderDir());
            $tokens = array();
            if (file_exists($tokensFile)) {
                $tokens = unserialize(file_get_contents($tokensFile));
            }
            $tokens[$name] = $token;
            $h = fopen($tokensFile, 'w');
            fwrite($h, serialize($tokens));
            fclose($h);
        }
    }

    public function core()
    {
    }

    public function getConfig($type, $name, $defaultValue = false)
    {
        if (isset($this->configs[$type])) {
            $config_nodes = explode('.', $name);
            $value = $this->configs[$type];
            foreach ($config_nodes as $node) {
                if (!isset($value[$node])) {
                    $value = $defaultValue;
                    return $defaultValue;
                }
                $value = $value[$node];
            }
            return $value;
        }
        return $defaultValue;
    }

    public static function get()
    {
        return call_user_func_array(
            array(
                self::getInstance(),
                'getConfig'
            ),
            func_get_args()
        );
    }

    public function getAllConfigs()
    {
        $configFiles = glob(sprintf('%s/*.dat', Environment::getUserGoaderDir()));
        foreach ($configFiles as $configFile) {
            $configContent = file_get_contents($configFile);
            $configs = unserialize($configContent);
            $host = rtrim(basename($configFile), '.dat');
            foreach ($configs as $key => $groups) {
                $this->combineConfig($host, $groups, $this->configs[$key]);
            }
        }
        return $this->configs;
    }

    protected function combineConfig($key, $groups, &$configs)
    {
        if (isset($configs[$key]) && is_array($groups)) {
            foreach ($groups as $key => $new_group) {
                $this->combineConfig($key, $new_group, $configs[$key]);
            }
        } else {
            $configs[$key]= $groups;
        }
    }
}
