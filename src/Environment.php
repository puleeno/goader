<?php
namespace Puleeno\Goader;

use Puleeno\Goader\Hosts\com\Duzhez;
use Puleeno\Goader\Clients\Downloader\Wget;

final class Environment
{
    private static $instance;

    public $goaderDir;
    public $goaderDataDir;
    public $userHomeDir;
    public $workDir;

    public $currentIndex;
    public $fileName;
    public $directoryName;
    public $goaderDirName;

    public function __construct()
    {
        $goaderBinDir = dirname(GOADER_MAIN_BIN_FILE);
        $this->goaderDir = dirname($goaderBinDir);

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->userHomeDir = sprintf('%s%s', getenv('HOMEDRIVE'), getenv('HOMEPATH'));
            $this->goaderDirName = 'Goader';
        } else {
            $this->userHomeDir = getenv('HOME');
            $this->goaderDirName = '.goader';
        }

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
            '%s/%s',
            self::getUserHomeDir(),
            self::getGoaderDirName(),
        );
    }

    public static function getCookiesDir()
    {
        return sprintf('%s/cookies.dat', self::getUserGoaderDir());
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

    public static function pluginDirectory($path = '')
    {
        return sprintf(
            '%s/plugins/%s',
            Environment::getGoaderDir(),
            $path
        );
    }

    public static function getNodeBinary()
    {
        if (empty($node = getenv('GOADER_NODE_BINARY'))) {
            return 'node';
        }
        return $node;
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

    public static function getDownloader()
    {
        return Wget::class;
    }
}
