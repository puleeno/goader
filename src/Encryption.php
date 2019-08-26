<?php
namespace Puleeno\Goader;

use Pikirasa\RSA;

class Encryption
{
    protected static $instance;
    public $encrypter;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $goaderHomeDir = sprintf('%s/.goader', Environment::getUserHomeDir());
        $rsaKeys = array(
            $goaderHomeDir . '/public.pem',
            $goaderHomeDir . '/private.pem',
        );
        list($pubKey, $privKey) = $rsaKeys;

        $this->encrypter = new RSA($pubKey, $privKey);
        if (!file_exists($privKey) || !file_exists($privKey)) {
            $this->encrypter->create();
        }
    }

    public static function encrypt($data)
    {
        return self::getInstance()
            ->encrypter
            ->encrypt($data);
    }

    public static function decrypt($encrypted)
    {
        return self::getInstance()
            ->encrypter
            ->decrypt($encrypted);
    }
}
