<?php
use Puleeno\Goader\Clients\Image\Merge;
use Puleeno\Goader\Command;
use Puleeno\Goader\Hook;

Hook::add_action('goader_init', function () {
    function goader_core_register_image_merge_command($runner, $args)
    {
        if (empty($args)) {
            return $runner;
        }

        $command = array_shift($args);
        if ($command === 'merge') {
            register_default_image_merge_command_options();

            $mergeClient = new Merge();
            return array($mergeClient, 'run');
        }
        return $runner;
    }

    function register_default_image_merge_command_options()
    {
        $command = Command::getCommand();
        $command->option('offset')
            ->describedAs('Offset chapter');

        $command->option('p')
            ->aka('prefix')
            ->describedAs('Use prefix file name');
    }

    Hook::add_filter('register_goader_command', 'goader_core_register_image_merge_command', 10, 2);
});
