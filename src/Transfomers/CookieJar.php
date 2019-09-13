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
    }
}
