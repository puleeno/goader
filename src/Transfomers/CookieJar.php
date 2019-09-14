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
        $cookie_arr = explode("\n", $cookies);
        $cookiejar = array();
        foreach ($cookie_arr as $cookie_str) {
            if (empty(trim($cookie_str))) {
                continue;
            }
            $cookie = $this->createCookieFromString($cookie_str);
            $cookiejar = $this->combineCookieJar($cookiejar, $cookie);
        }
        $this->cookieObjects = $cookiejar;
        $json = json_encode($this->cookieObjects);

        return str_replace('\/', '/', $json);
    }

    public function createCookieFromString($str)
    {
        $cookie_info = explode("\t", $str);
        if (count($cookie_info)!= 7) {
            exit('The cookies.txt is invalid');
        }

        $cookie_info[0] = ltrim($cookie_info[0], '.');
        list($host, $httpOnly, $path, $secure, $expiredAt, $name, $value) = $cookie_info;

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

    public function combineCookieJar($cookiejar, $cookie)
    {
        $cookie_object = [
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
        $cookie_object['creation'] = date('c', $time);
        $time = strtotime("-1 week", time());
        $cookie_object['lastAccessed'] = date('c', $time);
        $cookiejar[$cookie['host']][$cookie['path']][$cookie['name']] = $cookie_object;

        return $cookiejar;
    }
}
