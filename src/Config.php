<?php
namespace Puleeno\Goader;

use Puleeno\Goader\Environment;

class Config
{
    protected static $instance;

    protected $configDirectory;
    protected $configs;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->configDirectory = sprintf(
            '%s/configs',
            Environment::getUserGoaderDir()
        );
        $this->getConfigs();
    }

    public function getConfigs()
    {
    }

    public static function get($name) {

    }
}
