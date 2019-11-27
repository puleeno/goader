<?php
namespace Puleeno\Goader\Clients\Downloader;

use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;

class Wget
{
    protected $options = [];
    protected $supportedOptions = [];

    public function __construct($options = [])
    {
        $this->options = $options;
        $this->supported_options = Hook::apply_filters('goader_wget_client_supported_options', [
            'header',
            'O',
            'load-cookies',
            'quiet',
            'show-progress',
            'headers',
            'host',
        ]);
    }

    public function getContent($url, $fileName)
    {
        $command = $this->buildCommand($url, array_merge($this->options, [
            'O' => $fileName,
            'quiet' => true,
            'show-progress' => true,
        ]));
        $this->excuteCommand($command);
    }


    public function buildCommand($url, $options)
    {
        $command = '';
        foreach ($options as $key => $val) {
            if (!in_array($key, $this->supported_options)) {
                continue;
            }
            if ($key==='headers') {
                $key = 'header';
            }

            if ($key === 'host') {
                $cookieJarFile = sprintf(
                    '%s/hosts/%s.json',
                    Environment::getUserGoaderDir(),
                    Hook::apply_filters(
                        'goader_extract_cookie_jar_file_name',
                        $val
                    )
                );
                if (file_exists($cookieJarFile)) {
                    $command .= '--no-cookies ';
                    $json = json_decode(file_get_contents($cookieJarFile), true);
                    foreach($json as $host => $cookies) {
                        foreach($cookies as $path => $values) {
                            foreach($values as $cookie => $jar) {
                                $command .= sprintf('--header "Cookie: %s=%s" ', $cookie, $jar['value']);
                            }
                        }
                    }
                }
                continue;
            }

            switch (gettype($val)) {
                case 'array':
                    foreach ($val as $name => $v) {
                        if ($name === 'User-Agent') {
                            $command .= sprintf('--user-agent="%s" ', $v);
                            continue;
                        }

                        if (strlen($key) === 1) {
                            $command .= sprintf('-%s "%s: %s" ', $key, $name, $v);
                        } else {
                            $command .= sprintf('--%s="%s: %s" ', $key, $name, $v);
                        }
                    }
                    break;
                case 'boolean':
                    $command .= sprintf('--%s ', $key);
                    break;
                default:
                    if (strlen($key) === 1) {
                        $command .= sprintf('-%s "%s" ', $key, $val);
                    } else {
                        $command .= sprintf('--%s="%s" ', $key, $val);
                    }
                    break;
            }
        }
        return sprintf('%s %s "%s"', 'wget', trim($command), $url);
    }

    public function excuteCommand($command)
    {
        exec($command);
    }
}
