<?php

namespace Puleeno\Goader\Interfaces\Http;

interface ResponseInterface
{
    public function getStatusCode();

    public function getBody();
}
