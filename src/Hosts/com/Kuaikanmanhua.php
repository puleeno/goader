<?php
namespace Puleeno\Goader\Hosts\com;

use GuzzleHttp\Client;
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

    public function preparingJsonStr($str)
    {
        $json = $str;
        $pos = strpos($str, 'return');
        $searchPatterns = array();
        $replaces = array();

        if (is_numeric($pos)) {
            $json = substr($str, 0, $pos);
            $json = rtrim($json, ';');
            $json .= '}';
            $searchPatterns = array(
                '/\w\[(\d{1,})\]="/',
                '/";/'
            );
            $replaces = array(
                '"$1": "',
                '",'
            );
        } else {
            $searchPatterns = array(
                '/(width|height):[^,]*,\n?/',
                '/(url)/'
            );
            $replaces = array('', '"$1"');
        }

        $json = preg_replace($searchPatterns, $replaces, $json);
        return $json;
    }

    public function getImagesFromJsonStr($strJson)
    {
        $strJson = $this->preparingJsonStr($strJson);
        $json = json_decode($strJson, true);

        if (count($json) < 1) {
            return [];
        }
        $images = [];
        foreach ($json as $image) {
            $images[] = $image['url'];
        }

        return $images;
    }

    public function getChapterImagePattern()
    {
        return '/__NUXT__=\(function\([^\)]*\)\{return.+comicImages:(\[[^\]]*])/';
    }

    public function downloadChapter()
    {
        $this->content = (string)$this->getContent();
        if (!preg_match($this->getChapterImagePattern(), $this->content, $matches)) {
            Logger::log(sprintf('Occur error when download chapter has URL %s', $this->url));
        }

        $images = $this->getImagesFromJsonStr($matches[1]);
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
                    Logger::log(sprintf('Downloading image #%d has URL %s.', $index + 1, $image));
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
}
