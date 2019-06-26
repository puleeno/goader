<?php
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Host\io\Json;

Hook::add_action('goader_setup_command', function ($command) {
    $command->option('ch')
        ->aka('chapter')
        ->describedAs('Chapter ID');

    $command->option('chp')
        ->aka('path')
        ->describedAs('Chapter Path');
});