<?php
namespace Puleeno\Goader;

class Logger
{
    protected static $instance;

    protected $logsDir;

    public static function __callStatic($name, $args)
    {
        if (in_array($name, array('log', 'info', 'warning', 'success', 'error'))) {
            if ($name !== 'log' && !empty($args[0])) {
                $message = $args[0];
                $args[0] = sprintf('[%s]%s%s', strtoupper($name), date("yyyy-MM-dd'T'HH:mm:ss*SSSZZZZ"));

                if (!empty($args[1])) {
                    $args[0] .= sprintf(',%s ', $args[1]);
                }
                $args[0] .= $message;
            }
            call_user_func_array(array(self::instance(), 'write'), $args);
        } else {
            throw new \Exception('Method %s::%s() is not defined', Logger::class, $name);
        }
    }

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->logsDir = sprintf('%s/logs', Environment::getUserGoaderDir());
    }

    public function checkLogFile()
    {
        return sprintf(
            '%s/goader-%s.log',
            $this->logsDir,
            date('Y-m-d')
        );
    }

    public function write($message = '')
    {
        if ($message) {
            echo $message . PHP_EOL;
        }
    }

    public function writeLogFile($message)
    {
        $logFile = $this->checkLogFile();
        $h = fopen($logFile, 'w+');

        fwrite($h, $message);
        fclose($h);
    }
}
