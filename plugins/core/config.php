<?php
use Puleeno\Goader\Clients\Config;
use Puleeno\Goader\Command;
use Puleeno\Goader\Hook;

Hook::add_action('goader_init', function () {
    function register_default_config_command_options()
    {
        $command = Command::getCommand();
        $command->option('offset')
            ->describedAs('Offset chapter');

        $command->option('p')
            ->aka('prefix')
            ->describedAs('Use prefix file name');
    }

    function goader_core_register_config_command($runner, $args)
    {
        if (empty($args)) {
            return $runner;
        }
        $maybeUrl = end($args);
        $command = array_shift($args);

        if ($command === 'config') {
            register_default_config_command_options();

            $config = new Config($maybeUrl);
            return array($config, 'run');
        }
    }


    Hook::add_filter('register_goader_command', 'goader_core_register_config_command', 15, 2);
});
