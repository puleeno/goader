<?php
use Puleeno\Goader\Hook;
use Puleeno\Goader\Clients\Config;


Hook::add_action('goader_init', function () {
    function goader_core_register_config_command($runner, $args, $command)
    {
        if (empty($args)) {
            return $runner;
        }
        $maybeUrl = end($args);
        $command = array_shift($args);

        if ($command === 'config') {
            Hook::add_action('goader_setup_command', 'register_default_config_command_options');

            $config = new Config($maybeUrl);
            return array($config, 'run');
        }
    }

    function register_default_config_command_options($command)
    {
        $command->option('offset')
            ->describedAs('Offset chapter');

        $command->option('p')
            ->aka('prefix')
            ->describedAs('Use prefix file name');
    }

    Hook::add_filter('register_goader_command', 'goader_core_register_config_command', 10, 3);
}, 15);
