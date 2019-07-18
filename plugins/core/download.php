<?php
use Puleeno\Goader\Clients\Downloader;
use Puleeno\Goader\Command;
use Puleeno\Goader\Hook;

Hook::add_action('goader_init', function () {
    function register_options_for_download_command()
    {
        $command = Command::getCommand();
        $command->option('offset')
            ->describedAs('Offset chapter');

        $command->option('p')
            ->aka('prefix')
            ->describedAs('Use prefix file name');


        $command->option('s')
            ->aka('sequence')
            ->describedAs('Naming file with sequence number')
            ->boolean();
    }

    function goader_sequence_download_file_name($fileName, $currentIndex, $data)
    {
        $newFileName = $currentIndex;
        if (!empty($data['file_name_prefix'])) {
            $newFileName = sprintf('%s-%s', $data['file_name_prefix'], $currentIndex);
        }
        return $newFileName;
    }


    function goader_core_register_download_command($runner, $args)
    {
        if (empty($args)) {
            return $runner;
        }
        $maybeUrl = end($args);
        $command = array_shift($args);

        if ($command === 'download' || preg_match('/^https?:\/\//', $maybeUrl)) {
            register_options_for_download_command();

            Hook::do_action('goader_download_init');
            Hook::add_filter('image_sequence_file_name', 'goader_sequence_download_file_name', 10, 3);

            $downloader = new Downloader($maybeUrl);
            return array($downloader, 'run');
        }
        return $runner;
    }
    Hook::add_filter('register_goader_command', 'goader_core_register_download_command', 10, 2);
});
