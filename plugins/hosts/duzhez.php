<?php
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Host\io\Json;

function goader_core_register_duzhez_options()
{
    Hook::add_action('goader_setup_command', function ($command) {
        $command->option('c')
            ->aka('chapter')
            ->describedAs('Chapter ID');

        $command->option('chp')
            ->aka('path')
            ->describedAs('Chapter Path');
    });
}
Hook::add_action('goader_download_init', 'goader_core_register_duzhez_options', 10, 3);
