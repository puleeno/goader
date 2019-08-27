<?php
use Puleeno\Goader\Clients\Image\Extract;
use Puleeno\Goader\Command;
use Puleeno\Goader\Hook;

Hook::add_action('goader_init', function () {
    function goader_core_register_image_extract_command($runner, $args)
    {
        if (empty($args)) {
            return $runner;
        }

        $command = array_shift($args);
        if ($command === 'extract') {
            register_default_image_extract_command_options();

            $extractClient = new Extract();
            return array($extractClient, 'run');
        }
        return $runner;
    }

    function register_default_image_extract_command_options()
    {
        $command = Command::getCommand();
        // $command->option('e')
        //     ->aka('exclude')
        //     ->describedAs('Exclude image index in current working directory');

        $command->option('f')
            ->aka('format')
            ->describedAs('Image format output');

        $command->option('o')
            ->aka('output')
            ->describedAs('The output directory will be contains output images');

        $command->option('n')
            ->aka('num')
            ->describedAs('Extract each number of image to a image');

        $command->option('b')
            ->aka('begin')
            ->describedAs('Naming start with the number');

        $command->option('a')
            ->aka('all')
            ->describedAs('Extract all layers to file')
            ->boolean();
    }

    Hook::add_filter('register_goader_command', 'goader_core_register_image_extract_command', 10, 2);
});
