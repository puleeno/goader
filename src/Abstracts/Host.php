<?php
namespace Puleeno\Goader\Abstracts;

use Puleeno\Goader\Interfaces\HostInterface;
use Puleeno\Goader\Environment;
use GuzzleHttp\Client;
use Puleeno\Goader\Command;

abstract class Host implements HostInterface
{
    const NAME = '';

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
        return $this->content;
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
        $name = basename($originalName);
        $command = Command::getCommand();

        if ($command['sequence']) {
            $extension = $this->detecttExtension($originalName);
            $currentIndex = Environment::getCurrentIndex();
            $name = sprintf('%s.%s', $currentIndex, $extension);
            Environment::setCurrentIndex(++$currentIndex);
        }
        return $name;
    }

    public function detecttExtension($filename)
    {
        return pathinfo(
            $filename, PATHINFO_EXTENSION
        );
    }
}
