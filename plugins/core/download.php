<?php
use Puleeno\Goader\Hook;
use Puleeno\Goader\Clients\Downloader;

function goader_core_register_download_command($runner, $args, $command)
{
    if (empty($args)) {
        return $runner;
    }
    $maybeUrl = end($args);
    $command = array_shift($args);

    if ($command === 'download' || preg_match('/^https?:\/\//', $maybeUrl)) {
        $downloader = new Downloader($maybeUrl);

        return array($downloader, 'run');
    }
}
Hook::add_filter('register_goader_command', 'goader_core_register_download_command', 10, 3);
