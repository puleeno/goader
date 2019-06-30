<?php
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Hosts\io\Json;

Hook::add_action('goader_init', function () {
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
            return Json::class;
        }
    }

    Hook::add_filter('goaders', function ($hosts) {
        return array_merge($hosts, array(
            'json' => Json::class
        ));
    });

    Hook::add_action('goader_setup_command', function ($command) {
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
    });
}, 10);
