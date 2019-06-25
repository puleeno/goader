<?php
use Puleeno\Imagoader\Imagoader;

$composer = sprintf('%s/vendor/autoload.php', dirname(__FILE__));

if (file_exists($composer)) {
    require_once $composer;
}

if (class_exists(Imagoader::class)) {
    $imagoader = Imagoader::getInstance();

    $imagoader->run();
} else {
    exit('Please check your enviroment for run Imagoader');
}
