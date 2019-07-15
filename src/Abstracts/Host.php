<?php
namespace Puleeno\Goader\Abstracts;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use PHPHtmlParser\Dom;
use Puleeno\Goader\Clients\Http\Cloudscraper;
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Interfaces\HostInterface;
use Puleeno\Goader\Logger;
use Puleeno\Goader\Slug;

abstract class Host implements HostInterface
{
    const NAME = '';

    protected $url;
    protected $host;
    protected $content;

    protected $useCookie = false;
    protected $useCloudScraper = false;
    protected $cookieJar;
    protected $dirPrefix;
    protected $dom;

    protected $data = array();

    public function __construct($url, $host = null)
    {
        $this->url = $url;
        if (empty($host)) {
            $this->host = parse_url($url);
        } else {
            $this->host = $host;
        }

        $this->dom = new Dom();

        if ($this->useCookie) {
            // $this->loadCookie();
        }
    }

    public function __toString()
    {
        return $this->content;
    }


    public function getContent($url = '', $client = null, $method = 'GET', $options = array())
    {
        if (empty($url)) {
            $url = $this->url;
        }

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

        try {
            $res = $client->request(
                $method,
                $url,
                $options
            );

            if ($res->getStatusCode() < 400) {
                $newInstance->content = (string)$res->getBody();
            }
        } catch (\Exception $e) {
            Logger::log(sprintf('Error when download %s with message %s', $url, $e->getMessage()));
        }

        return $newInstance;
    }

    public function saveFile($filePath)
    {
        if (empty($this->content)) {
            return;
        }

        if (is_int(strpos($filePath, '/')) && !file_exists($dir = dirname($filePath))) {
            mkdir($dir, 0755, true);
        }
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
            $fileName = Hook::apply_filters(
                'image_sequence_file_name',
                $currentIndex,
                $currentIndex,
                $this->data,
            );

            $name = sprintf('%s.%s', $fileName, $extension);
            Environment::setCurrentIndex(++$currentIndex);
        }

        if (!empty($command['prefix'])) {
            $name = sprintf('%s-%s', $command['prefix'], $name);
        }

        if (!empty($this->dirPrefix)) {
            $name = trim($this->dirPrefix) . '/' . $name;
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

    protected function doNotSupport()
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

    public function getDirPrefix()
    {
        return $this->dirPrefix;
    }
}
