<?php
namespace Puleeno\Goader\Transfomers;

class CookieJar
{
    public $cookies;
    public $cookieObjects = [];

    public function __construct($txtCookies)
    {
        $this->cookies = $txtCookies;
    }

    public function cleanComments($cookies)
    {
        return preg_replace(array(
            '/^\#.+\n/m',
            '/\#\n/'
        ), '', $cookies);
    }

    public function convertToCookieJar()
    {
        $cookies = $this->cleanComments($this->cookies);
        $cookieArray = explode("\n", $cookies);
        $cookieJar = array();
        foreach ($cookieArray as $cookieStr) {
            if (empty(trim($cookieStr))) {
                continue;
            }
            $cookie = $this->createCookieFromString($cookieStr);
            $cookieJar = $this->combineCookieJar($cookieJar, $cookie);
        }
        $this->cookieObjects = $cookieJar;
        $json = json_encode($this->cookieObjects);

        return str_replace('\/', '/', $json);
    }

    public function createCookieFromString($str)
    {
        $cookieInfo = explode("\t", $str);
        if (count($cookieInfo)!= 7) {
            exit('The cookies.txt is invalid');
        }

        $cookieInfo[0] = ltrim($cookieInfo[0], '.');
        list($host, $httpOnly, $path, $secure, $expiredAt, $name, $value) = $cookieInfo;

        return [
            'host' => $host,
            'httpOnly' => $httpOnly,
            'path' => $path,
            'secure' => $secure,
            'expiredAt' => $expiredAt,
            'name' => $name,
            'value' => $value,
        ];
    }

    public function combineCookieJar($cookieJar, $cookie)
    {
        $cookieObj = [
            'key' => $cookie['name'],
            'value' => $cookie['value'],
            'expires' => date('c', $cookie['expiredAt']),
            'domain' => $cookie['host'],
            'path' => $cookie['path'],
            'secure' => $cookie['secure'],
            'httpOnly' => $cookie['httpOnly'],
            'hostOnly' => !in_array($cookie['name'], array('__cfduid')),
        ];

        $time = strtotime("-3 months", time());
        $cookieObj['creation'] = date('c', $time);
        $time = strtotime("-1 week", time());
        $cookieObj['lastAccessed'] = date('c', $time);
        $cookieJar[$cookie['host']][$cookie['path']][$cookie['name']] = $cookieObj;

        return $cookieJar;
    }
}
