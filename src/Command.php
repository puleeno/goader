<?php
namespace Puleeno\Goader;

use Commando\Command as Commando;

class Command
{
    protected static $instance;
    protected $command;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance  = new self();
        }
        return self::$instance;
    }


    public function __construct()
    {
        $this->command = new Commando();
    }

    public static function __callStatic($name, $args)
    {
        $cmd = self::getInstance();
        $command = $cmd->command;

        if (is_callable(array($command, $name))) {
            return call_user_func_array(array($command, $name), $args);
        }

        throw new \Exception(sprintf('Method %s::%s() is not defined', __CLASS__, $name));
    }

    public static function getCommand()
    {
        return self::getInstance()->command;
    }

    public static function defaultCommandOptions($command)
    {
        $command->useDefaultHelp(false);
        $command->option('help')
            ->describedAs('Get the Goader help');

        $command->option('s')
            ->aka('sequence')
            ->describedAs('Naming file with sequence number')
            ->boolean();
    }
}
