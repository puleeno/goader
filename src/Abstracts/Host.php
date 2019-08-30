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

    protected $useCookieJar = false;
    protected $useCloudScraper = false;
    protected $supportLogin = false;
    protected $isLoggedIn = false;
    protected $requiredLoggin = false;

    protected $cookieJar;
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

        if ($this->supportLogin) {
            $this->useCookieJar = true;
            $this->isLoggedIn = $this->checkLoggedin();
        }
        if ($this->requiredLoggin && empty($this->isLoggedIn)) {
            exit(
                sprintf('The host %s required loggin to download.', $this->host['host'])
            );
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
        $this->cookieJar = $cookieJarFile;
        if (!file_exists($this->cookieJar)) {
            $dir = dirname($this->cookieJar);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            $h = fopen($this->cookieJar, 'w+');
            fwrite($h, '');
            fclose($h);
        }
        return $this->cookieJar;
    }

    public function checkLoggedin()
    {
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
                $jar = $this->getCookieJar();
            } else {
                $client = new Client();
                $jar = new FileCookieJar($this->getCookieJar(), true);
            }
            if ($this->useCookieJar) {
                $options['cookies'] = $jar;
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
