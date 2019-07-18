<?php
use Puleeno\Goader\Hook;
use Puleeno\Goader\Clients\Image\Convert;


Hook::add_action('goader_init', function () {
    function goader_core_register_image_convert_command($runner, $args, $command)
    {
        if (empty($args)) {
            return $runner;
        }

        $command = array_shift($args);
        if ($command === 'merge') {
            Hook::add_action('goader_setup_command', 'register_default_image_convert_command_options');

            $convertClient = new Convert();
            return array($mergeClient, 'run');
        }
    }

    function register_default_image_convert_command_options($command)
    {
        $command->option('offset')
            ->describedAs('Offset chapter');

        $command->option('p')
            ->aka('prefix')
            ->describedAs('Use prefix file name');
    }

    Hook::add_filter('register_goader_command', 'goader_core_register_image_convert_command', 10, 3);
}, 50);
