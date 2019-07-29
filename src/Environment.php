<?php
namespace Puleeno\Goader;

use Puleeno\Goader\Hosts\com\Duzhez;

final class Environment
{
    private static $instance;

    public $goaderDir;
    public $userHomeDir;
    public $workDir;

    public $currentIndex;
    public $fileName;
    public $directoryName;

    public function __construct()
    {
        $goaderBinDir = dirname(GOADER_MAIN_BIN_FILE);

        $this->goaderDir = dirname($goaderBinDir);
        $this->userHomeDir = getenv('HOME');
        $this->workDir = getcwd();
        $this->currentIndex = 1;
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
        $environment->currentIndex = $index;
    }

    public static function supportedHosters()
    {
        $hosts = array(
            Duzhez::NAME => Duzhez::class
        );
        return Hook::apply_filters('goaders', $hosts);
    }

    public static function getUserGoaderDir()
    {
        return sprintf(
            '%s/.goader',
            self::userHomeDir()
        );
    }

    public static function getCookiesDir()
    {
        return sprintf('%s/cookies', self::getUserGoaderDir());
    }

    public static function getCommandArgs()
    {
        if (empty($_SERVER['argv'])) {
            return [];
        }
        $args = $_SERVER['argv'];

        /**
         * Remove file name in args
         */
        array_shift($args);
        return $args;
    }

    public static function getExtenions()
    {
        $extensions = [];
        $files = glob('*.*');
        foreach ($files as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $extension = strtolower($extension);
            if (!isset($extensions[$extension])) {
                $extensions[$extension] = 1;
            } else {
                $extensions[$extension]++;
            }
        }

        return $extensions;
    }
}
