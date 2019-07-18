<?php
use Puleeno\Goader\Hook;
use Puleeno\Goader\Clients\Downloader;

Hook::add_action('goader_init', function () {
    function goader_core_register_download_command($runner, $args, $command)
    {
        if (empty($args)) {
            return $runner;
        }
        $maybeUrl = end($args);
        $command = array_shift($args);

        if ($command === 'download' || preg_match('/^https?:\/\//', $maybeUrl)) {
            Hook::do_action('goader_download_init');

            Hook::add_filter('image_sequence_file_name', 'goader_sequence_download_file_name', 10, 3);
            Hook::add_action('goader_setup_command', 'register_options_for_download_command');

            $downloader = new Downloader($maybeUrl);
            return array($downloader, 'run');
        }
    }

    function goader_sequence_download_file_name($fileName, $currentIndex, $data)
    {
        $newFileName = $currentIndex;
        if (!empty($data['file_name_prefix'])) {
            $newFileName = sprintf('%s-%s', $data['file_name_prefix'], $currentIndex);
        }
        return $newFileName;
    }

    function register_options_for_download_command($command)
    {
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
    Hook::add_filter('register_goader_command', 'goader_core_register_download_command', 10, 3);
}, 20);
