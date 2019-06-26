<?php
namespace Puleeno\Goader\Abstracts;

use Puleeno\Goader\Interfaces\HostInterface;
use Puleeno\Goader\Environment;
use GuzzleHttp\Client;

abstract class Host implements HostInterface
{
    private static $name;

    protected $url;
    protected $data;
    protected $content;

    public function __construct($url, $data)
    {
        $this->url = $url;
        $this->data = $data;
    }

    public function __toString()
    {
        return $content;
    }

    public static function getName()
    {
        return self::$name;
    }

    public function getContent($url)
    {
        $currentClass = get_class($this);
        $newInstance = new $currentClass($this->url, $this->data);

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

    public function generateFileName($originalName)
    {
        $name = $originalName;
        if (true) {
            $extension = $this->detecttExtension($originalName);
            $currentIndex = Environment::getCurrentIndex();
            $name = sprintf('%s.%s', $currentIndex, $extension);
            Environment::setCurrentIndex(++$currentIndex);
        }
        return $name;
    }

    public function detecttExtension($filename)
    {
        return 'png';
    }

    public function formatLink($link)
    {
        return sprintf('http://mhimg.9mmc.com:44237/images/comic/371/741960/%s', $link);
    }
}
