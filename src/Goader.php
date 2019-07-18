<?php
namespace Puleeno\Goader;

use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Logger;

class Goader
{
    const GOADER_COMMAND = 'goader';

    protected static $instance;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        // Init environment for Goader
        Environment::getInstance();

        // Load the plugins to integrate with Goader
        $this->loadPlugins();
    }

    public function run()
    {
        Logger::info();

        Hook::do_action('goader_init');


        // Detect command via Goader core or Goader
        $commandArgs = Environment::getCommandArgs();
        $runner = Hook::apply_filters('register_goader_command', null, $commandArgs);

        // Check is runner is registered
        if (is_callable($runner)) {
            // Setup goader environment before run command
            Hook::do_action('setup_goader_environment', $this);

            call_user_func($runner);
        } else {
            $this->doNotSupportCommand($commandArgs[0]);
        }
    }

    public function doNotSupportCommand($command)
    {
        if (empty($command)) {
            $message = sprintf('Please type command `%s --help` to get the help', self::GOADER_COMMAND);
        } else {
            $message = sprintf('The command %s is not support', $command);
        }

        exit($message);
    }

    protected function loadPlugins()
    {
        Hook::do_action('goader_load_plugins');

        $goaderDir = Environment::getGoaderDir();
        $plugins = glob(sprintf('%s/plugins/{*,*/*}.php', $goaderDir), GLOB_BRACE);
        foreach ((array)$plugins as $plugin) {
            require_once $plugin;
        }

        Hook::do_action('goader_loaded_plugins');
    }
}
