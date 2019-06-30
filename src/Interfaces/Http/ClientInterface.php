<?php
namespace Puleeno\Goader\Interfaces\Http;

interface ClientInterface
{
    public function setUserAgent($agent);

    public function request($method, $uri = '', $options = array());
}
