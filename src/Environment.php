<?php
namespace Puleeno\Goader;

final class Environment
{
    private static $instance;

    public $userHomeDir;
    public $workDir;

    public $currentIndex;
    public $fileName;
    public $directoryName;

    public function __construct()
    {
        $this->userHomeDir = getenv('HOME');
        $this->workDir = getcwd();
        $this->currentIndex = 1;
        // $this->fileName = Command::option('t')
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public static function __callStatic($name, $args)
    {
        $property = ltrim($name, 'get');
        $property = strtolower(substr($property, 0, 1)) . substr($property, 1);
        $environment = self::getInstance();

        if (isset($environment->$property)) {
            return $environment->$property;
        }
        return false;
    }

    public static function setCurrentIndex($index)
    {
        $environment = self::getInstance();
        $environment->currentIndex($index);
    }
}
