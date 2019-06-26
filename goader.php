<?php
use Puleeno\Goader\Goader;

$composer = sprintf('%s/vendor/autoload.php', dirname(__FILE__));

if (file_exists($composer)) {
    require_once $composer;
}

if (class_exists(Goader::class)) {
    $Goader = Goader::getInstance();

    $Goader->run();
} else {
    exit('Please check your enviroment for run Goader');
}
