<?php
namespace Puleeno\Goader\Abstracts;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Puleeno\Goader\Clients\Http\Cloudscraper;
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Interfaces\HostInterface;

abstract class Host implements HostInterface
{
    const NAME = '';

    protected $url;
    protected $content;

    protected $useCookie = false;
    protected $useCloudScraper = false;
    protected $cookieJar;

    public function __construct($url)
    {
        $this->url = $url;

        if ($this->useCookie) {
            // $this->loadCookie();
        }
    }

    public function __toString()
    {
        return $this->content;
    }


    public function getContent($url, $method = 'GET', $client = null, $options = array())
    {
        $currentClass = get_class($this);
        $newInstance = new $currentClass($url);

        if (is_null($client)) {
            if (empty($this->useCloudScraper)) {
                $client = new Cloudscraper();
            } else {
                $client = new Client();
            }
            $client->setUserAgent('User-Agent: Mozilla/5.0 (Linux; U; Android 4.3; EN; C6502 Build/10.4.1.B.0.101) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30 PlayStation App/1.60.5/EN/EN');
        }

        $res = $client->request(
            $method,
            $url,
            $options
        );

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
            $filename,
            PATHINFO_EXTENSION
        );
    }

    protected function dontSupport()
    {
        exit(sprintf('We do not support download for URL %s', $this->url));
    }

    public function getCookieJarFile()
    {
        $cookieFileName = ltrim(get_class($this), 'Puleeno\Goader\Hosts\\');
        $cookieFile = sprintf(
            '%s/%s.cookie',
            Environment::getCookiesDir(),
            strtolower(str_replace('\\', '.', $cookieFileName))
        );

        return $cookieFile;
    }
}
