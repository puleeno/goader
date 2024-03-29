<?php
namespace Puleeno\Goader\Hosts\com;

use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Logger;

class Kuaikanmanhua extends Host
{
    const NAME = 'kuaikanmanhua';

    protected $dom;
    public $mangaID;
    public $chapterID;

    protected function checkPageType()
    {
        /**
         * Manga URL: https://www.kuaikanmanhua.com/web/topic/2631/
         * Chapter URI: https://www.kuaikanmanhua.com/web/comic/141851/
         *
         * 1: Manga
         * 2: Chapter
         */
        $pat = '/web\/(topic|comic)\/(\d{1,})\/?$/';
        if (preg_match($pat, $this->url, $matches)) {
            if ($matches[1] === 'topic') {
                $this->mangaID = $matches[2];
                return 1;
            }
            $this->chapterID = $matches[2];
            return 2;
        }
        return false;
    }

    public function getExtension($extension)
    {
        return 'jpg';
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

        $domChapters = $this->dom->find('.TopicList .TopicItem .title a');
        $chapters = array();
        foreach ($domChapters as $chapter) {
            $chapters[] = array(
                'chapter_link' => sprintf('%s://%s%s', $this->host['scheme'], $this->host['host'], $chapter->getAttribute('href')),
                'chapter_text' => $this->makeChapterNum($chapter->find('span')->text)
            );
        }

        $chapters = array_reverse($chapters);

        Logger::log(sprintf('This manga has %d chapters', count($chapters)));
        Logger::log('Downloading...');

        foreach ($chapters as $chapter) {
            $chapter_downloader = new self($chapter['chapter_link'], $this->host);
            $chapter_downloader->download($chapter['chapter_text']);
        }
        Logger::log('The manga is downloaded successfully!!');
    }


    public function getImagesFromJsonStr($strImages)
    {
        $images = array();

        if (preg_match_all('/(https\:[^\"]+)/', $strImages, $matches)) {
            foreach($matches[0] as $imageUrl) {
                $imageUrl = json_decode('"' . $imageUrl . '"');
                if (!$imageUrl) {
                    continue;
                }
                array_push($images, $imageUrl);
            }
        }
        return $images;
    }

    public function getChapterImagePattern()
    {
        return '/serverRendered\:e\}\}\((.+)Array\(/';
    }

    public function downloadChapter()
    {
        $this->content = (string)$this->getContent();

        if (!preg_match($this->getChapterImagePattern(), $this->content, $matches)) {
            Logger::log(sprintf('Occur error when download chapter has URL %s', $this->url));
            return;
        }

        $images = $this->getImagesFromJsonStr($matches[1]);
        $this->downloadImages($images);
    }

    public function formatLink($originalUrl)
    {
        $link = $originalUrl;
        $pre = Hook::apply_filters('kuaikanmanhua_filter_image_link', false, $link);
        if ($pre) {
            return $pre;
        }
        return $link;
    }
}
