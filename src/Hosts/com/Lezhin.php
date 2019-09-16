<?php
namespace Puleeno\Goader\Hosts\com;

use GuzzleHttp\Client;
use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Clients\Config;
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Logger;
use Puleeno\Goader\Encryption;

class Lezhin extends Host
{
    const NAME = 'lezhin';
    const LOGIN_END_POINT = 'en/login/submit';
    const CHAPTER_URL_FORMAT = '%s://www.%s/api/v2/inventory_groups/comic_viewer' .
        '?platform=web&store=web&alias=%s&name=%d&preload=true&type=comic_episode';

    protected $supportLogin = false;
    protected $isLoggedIn = false;
    protected $requiredLoggin = false;
    protected $dom;
    protected $csrf_token;

    public $mangaID;
    public $chapterID;

    protected function checkPageType()
    {
        $pat = '/comic\/([^\/]*)(\/\d{1,})$/';
        if (preg_match($pat, $this->url, $matches)) {
            $this->mangaID = $matches[1];
            if (isset($matches[2])) {
                $this->chapterID = ltrim($matches[2], '/');
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
        $page = $this->checkPageType();
        if ($page === false) {
            $this->doNotSupport();
            return;
        }
        if ($page === 1) {
            $this->downloadManga();
        } else {
            $this->downloadChapter();
        }
    }

    protected function makeChapterNum($text)
    {
        if (preg_match('/(\d{1,})/', $text, $matches)) {
            return sprintf('Chap %s', $matches[1]);
        }
        return $text;
    }

    public function downloadManga()
    {
        $this->content = (string)$this->getContent();
        $this->dom->load($this->content);

        $domChapters = $this->dom->find('ul#chapter a');
        $chapters = array();
        foreach ($domChapters as $chapter) {
            $chapters[] = array(
                'chapter_link' => $chapter->getAttribute('href'),
                'chapter_text' => $this->makeChapterNum($chapter->text)
            );
        }

        Logger::log(sprintf('This manga has %d chapters', count($chapters)));
        Logger::log('Downloading...');

        foreach ($chapters as $chapter) {
            $chapter_downloader = new self($chapter['chapter_link'], $this->host);
            $chapter_downloader->download($chapter['chapter_text']);
        }
        Logger::log('The manga is downloaded successfully!!');
    }

    public function getImagesFromJsonStr($strJson)
    {
        $json = json_decode($strJson, true);
        if (count($json) < 1) {
            return [];
        }
        $images = [];
        foreach ($json['image_list'] as $image) {
            $images[] = $image['src'];
        }
        return $images;
    }

    public function downloadChapter()
    {
        var_dump($this);die;
        $chapterHTML = (string)$this->getContent();

        $chapterUrl = sprintf(self::CHAPTER_URL_FORMAT, $this->host['scheme'], $this->host['host'], $this->chapterID);
        $this->content = (string)$this->getContent($chapterUrl);
        $images = $this->getImagesFromJsonStr($this->content);

        $total_images = count($images);
        if ($this->dirPrefix) {
            Logger::log(sprintf('The %s has %s images', strtolower($this->dirPrefix), $total_images));
        } else {
            Logger::log(sprintf('This chapter has %s images', $total_images));
        }


        if ($total_images > 0) {
            $httpClient = new Client();
            foreach ($images as $index => $image) {
                Environment::setCurrentIndex($index + 1);
                try {
                    Logger::log(sprintf('The image %s with URL %s is downloading...', $index + 1, $image));
                    $image_url = $this->formatLink($image);
                    if (!$this->validateLink($image_url)) {
                        Logger::log(sprintf('The url #%d is invalid with value "%s"', $index + 1, $image_url));
                        continue;
                    }

                    $fileName = $this->generateFileName($image_url, false);

                    $this->getContent($image_url, $httpClient)->saveFile($fileName);
                } catch (\Exception $e) {
                    Logger::log($e->getMessage());
                }
            }
            if ($this->dirPrefix) {
                Logger::log(sprintf('The %s is downloaded successfully!!', strtolower($this->dirPrefix)));
            } else {
                Logger::log(sprintf('The chapter is downloaded successfully!!'));
            }
            unset($images);
        }
    }

    public function formatLink($originalUrl)
    {
        $link = $originalUrl;
        $pre = Hook::apply_filters('u17_filter_image_link', false, $link);
        if ($pre) {
            return $pre;
        }
        return $link;
    }

    public function loggin()
    {
        $account = Config::get($this->getHostName(), 'accounts');
        if (empty($account)) {
            return false;
        }
        $login_url = sprintf('%s://%s/%s', $this->host['scheme'], $this->host['host'], self::LOGIN_END_POINT);
        $postdata = [
            'utf8' => '✓',
            'authenticity_token' => $this->csrf_token,
            'redirect' => '',
            'username' => $account['account'],
            'password' => Encryption::decrypt($account['password']),
            'remember_me' => 'on',
        ];
        var_dump($postdata);die;
        $options = array_merge($this->defaultHttpClientOptions(), array(
            'form_params' => $postdata,
        ));
        $response = $this->http_client->post($login_url, $options);
        var_dump($response);die;
        $this->getContent();
    }

    public function checkLoggedin()
    {
        $this->getContent();
        $this->dom->load($this->content);
        $csrf_token = $this->dom->find('input[name="authenticity_token"]');
        if (count($csrf_token) > 0) {
            $this->csrf_token = $csrf_token[0]->getAttribute('value');
        }

        return count($this->dom->find('.userInfo__email')) > 0;
    }
}
