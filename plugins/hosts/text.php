<?php
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Hosts\io\Text;

Hook::add_action('goader_init', function () {
    Hook::add_filter('custom_none_host', 'detect_text_file_download', 10, 2);
    function detect_text_file_download($host, $command)
    {
        $textFile = $command[1];

        if (!file_exists($textFile)) {
            return $host;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $textFile);
        finfo_close($finfo);

        if ($mime_type === 'text/plain') {
            register_text_command_options();
            return Text::class;
        }
        return $host;
    }

    function register_text_command_options()
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
});


Hook::add_filter('goaders', function ($hosts) {
    return array_merge($hosts, array(
        'text' => Text::class
    ));
});
