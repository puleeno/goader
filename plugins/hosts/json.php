<?php
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Hosts\io\Json;

function register_new_host_for_download($hosts)
{
    return array_merge($hosts, array(
        'json' => Json::class
    ));
}
Hook::add_filter('goaders', 'register_new_host_for_download');

Hook::add_action('goader_download_init', function () {
    function register_json_command_options()
    {
        $command = Command::getCommand();
        $command->option('h')
                    ->aka('host')
                    ->describedAs('Integrate with host configs via option')
                    ->must(function ($supportedHost) {
                        $supportedHosts = array_keys(Environment::supportedHosters());
                        return in_array($supportedHost, $supportedHosts);
                    });
        $command->option('u')
            ->aka('url')
            ->describedAs('Url prefix');
    }

    function detect_json_file_download($host, $command)
    {
        $jsonFile = $command[1];

        if (!file_exists($jsonFile)) {
            return $host;
        }

        $jsonStr = file_get_contents($jsonFile);
        $json = json_decode($jsonStr, true);

        if ($json) {
            register_json_command_options();
            return Json::class;
        }
        return $host;
    }
    Hook::add_filter('custom_none_host', 'detect_json_file_download', 10, 2);
});
