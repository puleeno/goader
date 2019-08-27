<?php
use Puleeno\Goader\Hook;
use Puleeno\Goader\Clients\File\Renamer;
use Puleeno\Goader\Command;

Hook::add_action('goader_init', function () {
    function register_default_file_rename_command_options()
    {
        $command = Command::getCommand();

        $command->option('f')
            ->aka('format')
            ->describedAs('Use prefix file name');

        $command->option('s')
            ->aka('sequence')
            ->boolean();

        $command->option('o')
            ->aka('output');

        $command->option('b')
            ->aka('begin')
            ->describedAs('The number use to start index of file name');
        $command->option('e')
            ->aka('extension')
            ->describedAs('Search file name with extension');

        $command->option('j')
            ->aka('jump')
            ->map(function ($jump) {
                return (int)$jump;
            });

        $command->option('r')
            ->aka('raw')
            ->describedAs('Keep don\'t sanitize file name and keep raw file name')
            ->boolean();

        $command->option('p')
            ->aka('prefix')
            ->describedAs('Use prefix file name');
    }

    function goader_core_register_file_rename_command($runner, $args)
    {
        if (empty($args)) {
            return $runner;
        }

        $command = array_shift($args);
        if ($command === 'rename') {
            register_default_file_rename_command_options();

            $renamer = new Renamer();
            return array($renamer, 'run');
        }
        return $runner;
    }

    Hook::add_filter('register_goader_command', 'goader_core_register_file_rename_command', 10, 2);
});
