<?php
use Puleeno\Goader\Hook;
use Puleeno\Goader\Clients\Image\Merge;


Hook::add_action('goader_init', function () {
    function goader_core_register_image_merge_command($runner, $args, $command)
    {
        if (empty($args)) {
            return $runner;
        }

        $command = array_shift($args);
        if ($command === 'merge') {
            Hook::add_action('goader_setup_command', 'register_default_image_merge_command_options');

            $mergeClient = new Merge();
            return array($mergeClient, 'run');
        }
    }

    function register_default_image_merge_command_options($command)
    {
        $command->option('offset')
            ->describedAs('Offset chapter');

        $command->option('p')
            ->aka('prefix')
            ->describedAs('Use prefix file name');
    }

    Hook::add_filter('register_goader_command', 'goader_core_register_image_merge_command', 10, 3);
}, 15);
