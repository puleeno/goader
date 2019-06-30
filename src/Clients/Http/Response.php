<?php
namespace Puleeno\Goader\Clients\Http;

use Puleeno\Goader\Interfaces\Http\ResponseInterface;

class Response implements ResponseInterface
{
    protected $body;
    protected $statusCode;

    public function __construct($body = '', $statusCode = nul)
    {
        $this->body = $body;
        if (!is_null($statusCode)) {
            $this->statusCode = $statusCode;
        }
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getBody()
    {
        return $this->body;
    }
}
