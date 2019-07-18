<?php
use Puleeno\Goader\Hook;
use Puleeno\Goader\Clients\File\Renamer;


Hook::add_action('goader_init', function () {
    function goader_core_register_file_rename_command($runner, $args, $command)
    {
        if (empty($args)) {
            return $runner;
        }

        $command = array_shift($args);
        if ($command === 'rename') {
            Hook::add_action('goader_setup_command', 'register_default_file_rename_command_options');
            $convertClient = new Convert();
            return array($mergeClient, 'run');
        }
    }

    function register_default_file_rename_command_options($command)
    {
        $command->option('f')
            ->aka('from')
            ->describedAs('Use prefix file name');
        $command->option('s')
            ->aka('sequence')

        $command->option('j')
            ->aka('jump');

        $command->option('p')
            ->aka('prefix')
            ->describedAs('Use prefix file name');
    }

    Hook::add_filter('register_goader_command', 'goader_core_register_file_rename_command', 10, 3);
}, 35);
