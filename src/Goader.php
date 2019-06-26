<?php
namespace Puleeno\Goader;

use Puleeno\Goader\Abstracts\Host;

class Goader
{
    protected static $instance;
    private $isCustomHost;

    protected $strUrl;
    protected $url;

    public $data = array();

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        Environment::getInstance();
        $this->loadPlugins();
    }

    public function run()
    {
        $command = Command::getCommand();
        $this->strUrl = $command[0];
        $this->url = parse_url($this->strUrl);

        if (isset($this->url['host'])) {
            $this->url['host'] = ltrim($this->url['host'], 'www.');
        }

        $host = isset($this->url['host']) ? $this->url['host'] : '';
        if (empty($host)) {
            $host = Hook::apply_filters('custom_none_host', $host, $command) ;

            if (empty($host)) {
                exit('Please provide the URL that you want to download images');
            } else {
                $this->isCustomHost = true;
            }
        }

        // Setup goader environment before run command
        Hook::do_action('setup_goader_environment', $this);

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

    protected function loadPlugins()
    {
        $goaderDir = Environment::getGoaderDir();
        $plugins = glob(sprintf('%s/plugins/*.php', $goaderDir));

        foreach ((array)$plugins as $plugin) {
            require_once $plugin;
        }
    }
}
