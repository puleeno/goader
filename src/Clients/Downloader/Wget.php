<?php
namespace Puleeno\Goader\Clients\Downloader;

use Puleeno\Goader\Hook;

class Wget {
    protected $options = [];
    protected $supportedOptions = [];

    public function __construct($options = []) {
        $this->options = $options;
        $this->supported_options = Hook::apply_filters('goader_wget_client_supported_options', [
            'header',
            'O',
            'load-cookies',
        ]);
    }

    public function getContent($url, $fileName) {
        $command = $this->buildCommand($url, array_merge($this->options, [
            'O' => $fileName
        ]));
        $this->excuteCommand($command);

    }


    public function buildCommand($url, $options) {
        $command = '';
        foreach($options as $key => $val) {
            if ($key==='headers') {
                $key = 'header';
            }
            switch (gettype($val)) {
                case 'array':
                    foreach($val as $name => $v) {
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
                default:
                    if (strlen($key) === 1) {
                        $command .= sprintf('-%s "%s" ', $key, $val);
                    } else {
                        $command .= sprintf('--%s="%s" ', $key, $val);
                    }
                    break;
            }
        }
        return sprintf('%s %s %s', 'wget', trim($command), $url);
    }

    public function excuteCommand($command) {
        exec($command);
    }
}
