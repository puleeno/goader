<?php
namespace Puleeno\Goader\Clients;

use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Clients\Helper;
use Puleeno\Goader\Command;
use Puleeno\Goader\Hook;

class Downloader
{
    protected $command;
    protected $url;
    protected $host;
    protected $isCustomHost;

    public function __construct($url)
    {
        $this->url = $url;
        $this->host = parse_url($this->url);
        $this->command = Command::getCommand();

        Hook::add_filter('custom_none_host', array($this, 'help'), 10, 2);
    }

    public function run()
    {

        if (isset($this->host['host'])) {
            $this->host['host'] = ltrim($this->host['host'], 'www.');
        }
        $host = isset($this->host['host']) ? $this->host['host'] : '';
        if (empty($host)) {
            $host = Hook::apply_filters('custom_none_host', $host, $this->command) ;
            if (empty($host)) {
                exit('Please provide the valid URL that you want to download images');
            } else {
                $this->isCustomHost = true;
            }
        }
        $action = Hook::apply_filters('goader_downloader', false, $host, $this->host, $this->url);
        if (is_callable($action)) {
            return call_user_func($action, $host, $this->host, $this->url);
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
            if (is_string($package_class) && class_exists($package_class)) {
                $downloader = new $package_class($this->url, $this->host);
                if (!$downloader instanceof Host) {
                    throw new \Exception(
                        sprintf('Class %s must is instance of %s', $package_class, Host::class)
                    );
                }
                $downloader->download();
            } elseif (is_callable($package_class)) {
                return call_user_func($package_class);
            } else {
                exit(sprintf('We don\'t support host %s', $host));
            }
        }
    }

    public function help($host, $command)
    {
        if ($this->url === '--help') {
            $helper = new Helper();
            return array($helper, 'download');
        }

        return $host;
    }
}
