<?php
namespace Puleeno\Goader;

use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Command;
use Puleeno\Goader\Hook;
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
        // Init commando

        // Init environment for Goader
        Environment::getInstance();

        // Load the plugins to integrate with Goader
        $this->loadPlugins();

        $this->initHooks();
    }

    public function run()
    {
        Hook::do_action('goader_init');

        Hook::do_action('goader_run');
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

    // phpcs:ignore
    public function _run()
    {
        // Detect command via Goader core or Goader
        $commandArgs = Environment::getCommandArgs();

        $runner = Hook::apply_filters('register_goader_command', null, $commandArgs);

        // Check is runner is registered
        if (is_callable($runner)) {
            // Setup goader environment before run command
            Hook::do_action('setup_goader_environment', $this);

            call_user_func($runner);
        } else {
            if (isset($commandArgs[0])) {
                $this->doNotSupportCommand($commandArgs[0]);
            }
        }
    }

    public function initHooks()
    {
        Hook::add_action('goader_run', array($this, '_run'));
    }
}
