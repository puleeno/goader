<?php
namespace Puleeno\Goader\Hosts\com;

use Cocur\Slugify\Slugify;
use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Clients\Config;
use Puleeno\Goader\Command;
use Puleeno\Goader\Encryption;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Logger;

class Fanboylove extends Host
{
    const NAME = 'fanboylove';

    protected $dom;

    protected $useCloudScraper = true;
    protected $useCookieJar = true;
    protected $supportLogin = true;
    protected $chapter_url;

    protected function checkPageType()
    {
        if (preg_match('/truyen\/([^\/]*)\/([^\/]*)?/', $this->url, $matches)) {
            if (isset($matches[2])) {
                return 2;
            }
            return 1;
        }
        return false;
    }

    public function download($directoryName = null)
    {
        if (!empty($directoryName)) {
            $this->dirPrefix = $directoryName;
        }
        $this->getContent();

        if ($this->requiredLoggin = (strpos($this->content, 'fanbl.art/lock-request/login-request') !== false)) {
            $this->chapter_url = $this->url;
            $this->loggin();
        }

        if ($this->requiredLoggin && !$this->isLoggedIn) {
            exit('Please login before download data');
        }

        $pageType = $this->checkPageType();
        if ($pageType === 1) {
            $this->downloadManga();
        } elseif ($pageType === 2) {
            $this->downloadChapter();
        } else {
            $this->doNotSupport();
        }
    }

    public function checkLoggedIn()
    {
    }

    public function downloadManga()
    {
    }

    public function detectExtension($fileName)
    {
        $ext = pathinfo(
            $fileName,
            PATHINFO_EXTENSION
        );
        if ($ext === 'js') {
            return 'jpg';
        }

        return $ext;
    }

    public function downloadChapter()
    {
        $this->dom->load($this->content);

        $chapterArr = explode('/', trim($this->host['path'], '/'));
        $chapterName = end($chapterArr);

        if ($chapterName) {
            $slugify = new Slugify();
            $this->data['file_name_prefix'] = $slugify->slugify($chapterName);
        }

        $domImages = $this->dom->find('.box-reading-content .page-break img');
        $images = [];
        foreach ($domImages as $domImage) {
            $images[] = trim($this->formatLink($domImage->getAttribute('data-src')));
        }

        $totalImages = count($images);
        if ($this->dirPrefix) {
            Logger::log(sprintf('The %s has %s images', strtolower($this->dirPrefix), $totalImages));
        } else {
            Logger::log(sprintf('This chapter has %s images', $totalImages));
        }
        $this->downloadImages($images, [
            'headers' => [
                'Referer'   => $this->url,
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A356 Safari/604.1',
            ],
        ]);
    }

    public function formatLink($originalUrl)
    {
        $link = $originalUrl;

        $pre = Hook::apply_filters('fanboylove_filter_image_link', false, $link);
        if ($pre) {
            return $pre;
        }

        if (preg_match('/url=(https?.+.\w{1,4})/', $link, $matches)) {
            $link = $matches[1];
        }
        return $link;
    }

    public function loggin()
    {
        $login_url = sprintf(
            '%s://%s/wp-admin/admin-ajax.php?action=wp_manga_signin',
            $this->host['scheme'],
            $this->host['host'],
        );
        $account = Config::get($this->getHostName(), 'accounts');
        if (empty($account)) {
            return;
        }
        $password = Encryption::decrypt($account['password']);
        $content = $this->getContent($login_url, null, 'POST', array(
            'formData' => array(
                'login' => $account['account'],
                'pass' => $password,
                'rememberme' => 'forever',
            ),
        ));
        $json = json_decode($content);
        if (isset($json->success) && $json->success) {
            $this->isLoggedIn = true;
            $this->url = $this->chapter_url;
            $this->getContent();
        }
    }
}
