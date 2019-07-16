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

    public function getName()
    {
        return $this::NAME;
    }


    public function getContent($url = '', $client = null, $method = 'GET', $options = array())
    {
        if (empty($url)) {
            $url = $this->url;
        }
        $currentClass = get_class($this);


        $newInstance = new $currentClass($url);

        if (is_null($client)) {
            if (!empty($this->useCloudScraper)) {
                $client = new Cloudscraper();
            } else {
                $client = new Client();
            }
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
            Logger::log(sprintf('Error when download #%d with URL %s', Environment::getCurrentIndex(), $url));
            // $e->getMessage();
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

    public function generateFileName($originalName, $autoIncreaIndex = true)
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

            if ($autoIncreaIndex) {
                Environment::setCurrentIndex(++$currentIndex);
            }
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

    public function validateLink($link)
    {
        if (empty($link)) {
            return false;
        }
        return preg_match('/^https?:\/\//', $link) && is_int(strpos($link, '.'));
    }
}
