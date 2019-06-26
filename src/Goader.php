<?php
namespace Puleeno\Goader;

use Puleeno\Goader\Abstracts\Host;

class Goader
{
    protected static $instance;
    protected $str_url;
    protected $url;

    public static function getInstance()
    {
        if(is_null(self::$instance))
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        Environment::getInstance();
    }

    public function run()
    {
        $command = Command::getCommand();
        $this->str_url = $command[0];
        $this->url = parse_url($this->str_url);

        if (isset($this->url['host'])) {
            $this->url['host'] = ltrim($this->url['host'], 'www.');
        }

        $host = isset($this->url['host']) ? $this->url['host'] : '';
        if (empty($host)) {
            exit('Please provide the URL that you want to download images');
        }

        $action = Hook::apply_filters('Goader_downloader',false, $host, $this->url, $this->str_url);
        if (is_callable($action)) {
            return call_user_func($action, $host, $this->url, $this->str_url);
        } else {
            $packages = explode('.', $host);
            $packages[0] = ucfirst($packages[0]);

            array_push($packages, 'Host', 'Goader', 'Puleeno');

            $package_class = implode('\\', array_reverse($packages));
            if (class_exists($package_class)) {
                $downloader = new $package_class($this->str_url);
                if (!$downloader instanceof Host) {
                    throw new \Exception(
                        sprintf('Class %s must is instance of %s', $package_class, Host::class)
                    );
                }
                $downloader->download();
            }
        }
    }
}
