<?php

namespace Puleeno\Goader\Clients;

class Config
{
    protected $supported = ['account', 'host', 'core'];

    public function __construct()
    {
    }

    public function account()
    {
    }

    /**
     * List host configs
     *
     * - load-cookies: Convert cookies.txt from browser export to CookieJar
     */
    public function host()
    {
    }

    public function core()
    {
    }
}
