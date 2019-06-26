<?php
namespace Puleeno\Goader\Abstracts;


use Puleeno\Goader\Interfaces\HostInterface;
use Puleeno\Goader\Environment;
use GuzzleHttp\Client;

abstract class Host implements HostInterface
{
    protected $url;
    protected $content;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function getContent($url)
    {
        $newInstance = new self($url);

        $client = new Client();
        $res = $client->request('GET', $url);

        if ($res->getStatusCode() < 400) {
            $newInstance->content = (string)$res->getBody();
        }

        return $newInstance;
    }

    public function saveFile($filePath)
    {
        $h = fopen($filePath, 'w');
        fwrite($h, $this->content);
        fclose($h);
    }

    public function generateFileName()
    {
        return Environment::getCurrentIndex();
    }

    public function detecttExtension($filename)
    {
        return 'png';
    }

    public function __toString()
    {
        return $content;
    }
}
