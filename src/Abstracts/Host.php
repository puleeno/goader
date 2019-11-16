<?php
namespace Puleeno\Goader\Abstracts;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use PHPHtmlParser\Dom;
use Puleeno\Goader\Clients\Http\Cloudscraper;
use Puleeno\Goader\Clients\Downloader\Wget;
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

    protected $useCookieJar = false;
    protected $useCloudScraper = false;
    protected $supportLogin = false;
    protected $isLoggedIn = false;
    protected $requiredLoggin = false;
    protected $http_client;
    protected $cookieJarFile;
    protected $dirPrefix;
    protected $dom;
    protected $defaultExtension = 'jpg';

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
        $this->createHttpClient();

        if ($this->supportLogin) {
            $this->useCookieJar = true;
            $this->isLoggedIn = $this->checkLoggedin();
        }
        if ($this->requiredLoggin && empty($this->isLoggedIn)) {
            Logger::log(sprintf('Loggin to %s...', $this->host['host']));
            if (!$this->loggin()) {
                exit(
                    sprintf('The host %s required loggin to download.', $this->host['host'])
                );
            } else {
                Logger::log('Logged in!');
            }
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

    public function createHttpClient($client = null)
    {
        if ($client) {
            $this->http_client = $client;
            return;
        }
        if (!empty($this->useCloudScraper)) {
            $this->http_client = new Cloudscraper($this->defaultHttpClientOptions());
        } else {
            $this->http_client = new Client($this->defaultHttpClientOptions());
        }
    }

    public function getHostName()
    {
        if (empty($this->host)) {
            return $this->getName();
        }
        return ltrim($this->host['host'], 'www.');
    }

    protected function getCookieJar()
    {
        $cookieJarFile = sprintf(
            '%s/hosts/%s.json',
            Environment::getUserGoaderDir(),
            Hook::apply_filters(
                'goader_extract_cookie_jar_file_name',
                $this->host['host']
            )
        );
        $this->cookieJarFile = $cookieJarFile;
        if (!file_exists($this->cookieJarFile)) {
            $dir = dirname($this->cookieJarFile);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            $h = fopen($this->cookieJarFile, 'w+');
            fwrite($h, '');
            fclose($h);
        }
        return $this->cookieJarFile;
    }

    public function loggin()
    {
    }

    public function checkLoggedin()
    {
    }

    public function getContent($url = '', $client = null, $method = 'GET', $options = array())
    {
        if (empty($url)) {
            $url = $this->url;
        }
        $options = array_merge($this->defaultHttpClientOptions(), $options);
        if ($client) {
            $this->createHttpClient($client);
        }
        try {
            $res = $this->http_client->request($method, $url, $options);
            $this->content = (string)$res->getBody();
        } catch (\Exception $e) {
            Logger::log(sprintf('Error when download #%d with URL %s', Environment::getCurrentIndex(), $url));
            Logger::log($e->getMessage());
            $this->content = '';
        }
        return $this;
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
            $extension = $this->detectExtension($originalName);
            $currentIndex = Environment::getCurrentIndex();
            $fileName = Hook::apply_filters(
                'image_sequence_file_name',
                $currentIndex,
                $currentIndex,
                $this->data,
            );

            $name = sprintf('%s.%s', $fileName, $this->getExtension($extension));

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

    public function getExtension($extension)
    {
        if (empty($extension)) {
            $extension = $this->defaultExtension();
        }
        return Hook::apply_filters($this->getName() . '_file_extension', $extension);
    }

    public function defaultExtension()
    {
        return Hook::apply_filters(
            $this->getName() . '_the_file_extension',
            $this->defaultExtension
        );
    }

    public function detectExtension($filename)
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
        $cookieFileName = $this->getHostName();
        $cookieFile = sprintf(
            '%s/%s.cookie',
            Environment::getCookiesDir(),
            strtolower(str_replace('\\', '.', $cookieFileName))
        );

        return $cookieFile;
    }

    public function defaultHttpClientOptions()
    {
        $options = [
            'verify' => false,
            'User-Agent'=>'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36',
        ];

        if ($this->useCookieJar) {
            $jar = $this->useCloudScraper ? $this->getCookieJar() : new FileCookieJar($this->getCookieJar(), true);
            $options['cookies'] = $jar;
        }
        return $options;
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

    protected function downloadClientOptions()
    {
        return [];
    }

    public function downloadImages($images)
    {
        $downloader = new Wget($this->downloadClientOptions());
        $total_images = count($images);
        if ($this->dirPrefix) {
            Logger::log(sprintf('The %s has %s images', strtolower($this->dirPrefix), $total_images));
        } else {
            Logger::log(sprintf('This chapter has %s images', $total_images));
        }
        foreach ($images as $index => $image) {
            Environment::setCurrentIndex($index + 1);
            try {
                $image_url = $this->formatLink($image);
                Logger::log(sprintf('Downloading image #%d has URL %s.', $index + 1, $image_url));
                if (!$this->validateLink($image_url)) {
                    Logger::log(sprintf('The url #%d is invalid with value "%s"', $index + 1, $image_url));
                    continue;
                }

                $fileName = $this->generateFileName($image_url, false);
                $downloader->getContent($image_url, $fileName);
            } catch (\Exception $e) {
                Logger::log($e->getMessage());
            }
        }
        if ($this->dirPrefix) {
            Logger::log(sprintf('The %s is downloaded successfully!!', strtolower($this->dirPrefix)));
        } else {
            Logger::log(sprintf('The chapter is downloaded successfully!!'));
        }
    }
}
