<?php
use Puleeno\Goader\Hook;

use Puleeno\Goader\Clients\Helper;

function goader_register_help_command($command, $args)
{
    if (count($args) === 1 && $args[0] === '--help') {
        $helper = new Helper();
        $command = array($helper, 'show');
    }
    return $command;
}
Hook::add_filter('register_goader_command', 'goader_register_help_command', 10, 2);
