<?php
namespace Puleeno\Goader\Hosts\com;

use GuzzleHttp\Client;
use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Logger;

class U17 extends Host
{
    const NAME = 'u17';

    protected $dom;

    public $mangaID;
    public $chapterID;

    const CHAPTER_URL_FORMAT= '%s://www.%s/comic/ajax.php?mod=chapter&act=get_chapter_v5&chapter_id=%s';

    protected function checkPageType()
    {
        /**
         * Manga URL: http://www.u17.com/comic/144098.html
         * Chapter URI: http://www.u17.com/chapter/580167.html
         *
         * 1: Manga
         * 2: Chapter
         */
        $pat = '/com\/(comic|chapter)\/(\d{1,}).html$/';
        if (preg_match($pat, $this->url, $matches)) {
            if ($matches[1] === 'comic') {
                $this->mangaID = $matches[2];
                return 1;
            }
            $this->chapterID = $matches[2];
            return 2;
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
        $chapterUrl = sprintf(self::CHAPTER_URL_FORMAT, $this->host['scheme'], $this->host['host'], $this->chapterID);

        $this->content = (string)$this->getContent($chapterUrl);
        $images = $this->getImagesFromJsonStr($this->content);

        $this->downloadImages($images);
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
}
