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
    const CHAPTER_URL_FORMAT = '%1$s/api/v2/inventory_groups/comic_viewer' .
        '?platform=web&store=web&alias=%2$s&name=%3$d&preload=true&type=comic_episode';
    const CDN_IMAGE_FORMAT = '%1$s://cdn.%2$s/v2%3$s?access_token=%4$s&purchased=true&q=30&updated=%d';

    protected $supportLogin = false;
    protected $isLoggedIn = false;
    protected $requiredLoggin = false;
    protected $dom;
    protected $csrf_token;
    protected $useCloudScraper = false;
    protected $userToken;

    public $comicId;
    public $chapterId;

    protected function checkPageType()
    {
        $pat = '/comic\/([^\/]*)(\/\d{1,})$/';
        if (preg_match($pat, $this->url, $matches)) {
            $this->comicId = $matches[1];
            if (isset($matches[2])) {
                $this->chapterId = ltrim($matches[2], '/');
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
        $host = $this->host;
        unset($host['path']);
        $host = implode('://', $host);
        $this->url = sprintf(self::CHAPTER_URL_FORMAT, $host, $this->comicId, $this->chapterId);
        $response = (string)$this->getContent();
        if (!$response) {
            exit(sprintf('Can not get information fron %s', $this->host['host']));
        }
        $chapterInfo = json_decode($response, true);
        if (!$chapterInfo) {
            exit(sprintf('Invalid response from %s', $this->host['host']));
        }
        if (empty($chapterInfo['data']['extra']['episode']['scrollsInfo'])) {
            exit(sprintf('Can not get images list from %s', $this->host['host']));
        }
        $images = $chapterInfo['data']['extra']['episode']['scrollsInfo'];
        $extracedImages = [];
        foreach ($images as $image) {
            $extracedImages[] = $image['path'];
        }
        if (!$this->isLoggedIn) {
            $token = Config::get($this->getHostName(), 'tokens');
            if (empty($token)) {
                exit(sprintf('Please login or use token to download at %s', $this->host['host']));
            }
        }
        $this->userToken = $token;
        $this->downloadImages($extracedImages);
    }

    public function formatLink($originalUrl)
    {
        $link = $originalUrl;
        $pre = Hook::apply_filters('lezhin_filter_image_link', false, $link);
        if ($pre) {
            return $pre;
        }
        https://cdn.lezhin.com/v2/comics/5862821801623552/episodes/4542142846205952/contents/scrolls/1?access_token=bab22130-39f9-4363-9113-71f6ba63f945&q=30&updated=1566266756710
        $time = time();
        $link = sprintf(
            self::CDN_IMAGE_FORMAT,
            $this->host['scheme'],
            ltrim($this->host['host'], 'www.'),
            $originalUrl,
            $this->userToken,
            $time
        );
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
        $options = array_merge($this->defaultHttpClientOptions(), array(
            'form_params' => $postdata,
        ));
        $response = $this->http_client->post($login_url, $options);
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
