<?php
use Puleeno\Goader\Hook;

Hook::add_filter('custom_none_host', 'detect_json_file_download', 10, 2);
function detect_json_file_download($host, $command)
{
    $jsonFile = $command[0];
    if (!file_exists($jsonFile)) {
        return $host;
    }
    $jsonStr = file_get_contents($jsonFile);
    $json = json_decode($jsonStr, true);

    Hook::add_action('setup_goader_environment', function ($goader) use ($json) {
        $goader->data['json'] = $json;
    });

    if ($json) {
        return 'Puleeno\Goader\Host\io\Json';
    }
}
