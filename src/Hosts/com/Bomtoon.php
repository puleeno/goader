<?php
namespace Puleeno\Goader\Hosts\com;

use GuzzleHttp\Client;
use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Logger;

class Bomtoon extends Host
{
    protected $dom;

    public $comicID;
    public $chapterID;
    protected $useCloudScraper = true;

    const CHAPTER_URL_FORMAT= '%s://www.%s/comic/ajax.php?mod=chapter&act=get_chapter_v5&chapter_id=%s';

    protected function checkPageType()
    {
        /**
         * Manga URL: https://www.bomtoon.com/comic/ep_list/mistakelov
         * Chapter URI: https://www.bomtoon.com/comic/ep_view/mistakelov/3
         *
         * 1: Manga
         * 2: Chapter
         */
        $pat = '/comic\/(ep_list|ep_view)\/([^\/]*)(\/\d{1,})?$/';
        if (preg_match($pat, $this->url, $matches)) {
            $this->comicID = $matches[2];
            if (isset($matches[3])) {
                $this->chapterID = $matches[3];
                return 2;
            } else {
                return 1;
            }
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

        $domChapters = $this->dom->find('ul#bt-episode-rent-list li a');
        $chapters = array();
        foreach ($domChapters as $chapter) {
            $chapters[] = array(
                'chapter_link' => $chapter->getAttribute('href'),
                'chapter_text' => $this->makeChapterNum($chapter->find('em')->text)
            );
        }
        $chapters = array_reverse($chapters);

        Logger::log(sprintf('This manga has %d chapters', count($chapters)));
        Logger::log('Downloading...');

        foreach ($chapters as $chapter) {
            $chapter_downloader = new self($chapter['chapter_link']);
            $chapter_downloader->download($chapter['chapter_text']);
        }
        Logger::log('The manga is downloaded successfully!!');
    }

    public function downloadChapter()
    {
        $this->content = (string)$this->getContent();
        var_dump($this->content);
        die;
        $this->dom->load($this->content);

        $images = $this->dom->find('#bt-webtoon-image img');

        $total_images = count($images);
        if ($this->dirPrefix) {
            Logger::log(sprintf('The %s has %s images', strtolower($this->dirPrefix), $total_images));
        } else {
            Logger::log(sprintf('This chapter has %s images', $total_images));
        }


        // if ($total_images > 0) {
        //     $httpClient = new Client();
        //     foreach ($images as $index => $image) {
        //         Environment::setCurrentIndex($index + 1);
        //         try {
        //             Logger::log(sprintf('The image %s with URL %s is downloading...', $index + 1, $image));
        //             $image_url = $this->formatLink($image);
        //             if (!$this->validateLink($image_url)) {
        //                 Logger::log(sprintf('The url #%d is invalid with value "%s"', $index + 1, $image_url));
        //                 continue;
        //             }

        //             $fileName = $this->generateFileName($image_url, false);
        //             $this->getContent($image_url, $httpClient)->saveFile($fileName);
        //         } catch (\Exception $e) {
        //             Logger::log($e->getMessage());
        //         }
        //     }
        //     if ($this->dirPrefix) {
        //         Logger::log(sprintf('The %s is downloaded successfully!!', strtolower($this->dirPrefix)));
        //     } else {
        //         Logger::log(sprintf('The chapter is downloaded successfully!!'));
        //     }
        //     unset($images);
        // }
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
