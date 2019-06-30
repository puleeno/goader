<?php
namespace Puleeno\Goader\Clients;

use Puleeno\Goader\Hook;
use Puleeno\Goader\Abstracts\Host;

class Downloader
{
    protected $url;
    protected $host;

    public function __construct($url)
    {
        $this->url = $url;
        $this->host = parse_url($this->url);
    }

    public function run($command)
    {
        if (isset($this->host['host'])) {
            $this->host['host'] = ltrim($this->host['host'], 'www.');
        }
        $host = isset($this->host['host']) ? $this->host['host'] : '';
        if (empty($host)) {
            $host = Hook::apply_filters('custom_none_host', $host, $command) ;

            if (empty($host)) {
                exit('Please provide the valid URL that you want to download images');
            } else {
                $this->isCustomHost = true;
            }
        }
        $action = Hook::apply_filters('goader_downloader', false, $host, $this->host, $this->url);
        if (is_callable($action)) {
            return call_user_func($action, $host, $this->host, $this->url, $this->data);
        } else {
            if (empty($this->isCustomHost)) {
                $packages = explode('.', $host);
                $packages[0] = ucfirst($packages[0]);

                array_push($packages, 'Hosts', 'Goader', 'Puleeno');
                $packages =  array_reverse($packages);

                $package_class = implode('\\', $packages);
            } else {
                $package_class = $host;
            }
            if (class_exists($package_class)) {
                $downloader = new $package_class($this->url);
                if (!$downloader instanceof Host) {
                    throw new \Exception(
                        sprintf('Class %s must is instance of %s', $package_class, Host::class)
                    );
                }
                $downloader->download();
            } else {
                exit(sprintf('We don\'t support host %s', $host));
            }
        }
    }
}
