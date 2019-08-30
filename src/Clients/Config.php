<?php
namespace Puleeno\Goader\Clients;

use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Encryption;

class Config
{
    protected $supported = ['account', 'host', 'core'];
    protected $commands   = [];
    protected $options = [];
    protected $configIsLoaded = false;
    protected $configs = [];


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
    }

    public function core()
    {
    }

    public function getConfig($name, $defaultValue = false)
    {
        $config_nodes = explode('.', $name);
    }
}
