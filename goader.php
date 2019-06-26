#!/usr/bin/env php
<?php
use Puleeno\Goader\Goader;

$composer = false;

$searchComposers = array(
    sprintf('%s/vendor/autoload.php', dirname(__FILE__)),
    sprintf('%s/../../autoload.php', dirname(__FILE__))
);

foreach ($searchComposers as $searchComposer) {
    $searchComposers = realpath($searchComposer);
    if (file_exists($searchComposers)) {
        $composer = $searchComposers;
        break;
    }
}

if ($composer) {
    require_once $composer;
}

if (class_exists(Goader::class)) {
    $Goader = Goader::getInstance();

    $Goader->run();
} else {
    exit('Please check your enviroment for run Goader');
}
