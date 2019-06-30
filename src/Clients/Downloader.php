<?php
namespace Puleeno\Goader\Clients;

class Downloader
{
    public function __construct($url)
    {
    }

    public function run()
    {
        $action = Hook::apply_filters('goader_downloader', false, $host, $this->url, $this->strUrl);
        if (is_callable($action)) {
            return call_user_func($action, $host, $this->url, $this->strUrl, $this->data);
        } else {
            if (empty($this->isCustomHost)) {
                $packages = explode('.', $host);
                $packages[0] = ucfirst($packages[0]);

                array_push($packages, 'Host', 'Goader', 'Puleeno');
                $packages =  array_reverse($packages);

                $package_class = implode('\\', $packages);
            } else {
                $package_class = $host;
            }

            if (class_exists($package_class)) {
                $downloader = new $package_class($this->strUrl, $this->data);
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
