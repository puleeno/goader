<?php
namespace Puleeno\Goader\Hosts\com;

use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Logger;

class Iqiyi extends Host
{
    const NAME = 'iqiyi';

    protected $dom;

    protected function checkPageType()
    {
        /**
         * 1: Manga
         * 2: Chapter
         */
        $pat = '/manhua\/(reader|detail)[\/|_].+$/';
        if (preg_match($pat, $this->url, $matches)) {
            if ($matches[1] === 'detail') {
                return 1;
            }
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

    public function downloadManga()
    {
        $this->content = (string)$this->getContent();
        $this->dom->load($this->content);

        $domChapters = $this->dom->find('#cata_cont_list ol li a');
        $chapters = array();
        foreach ($domChapters as $chapter) {
            $chapters[] = array(
                'chapter_link' => sprintf('%s://%s%s', $this->host['scheme'], $this->host['host'], $chapter->getAttribute('href')),
                'chapter_text' => trim($chapter->find('span.itemcata-title')->text)
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

    public function getImagesFromHTML($content)
    {
        $images = [];
        $this->dom->load($content);
        $dom_images = $this->dom->find('ul.main-container li.main-item img');
        foreach ($dom_images as $dom_image) {
            $images[] = $dom_image->getAttribute('data-original');
        }
        return $images;
    }


    public function downloadChapter()
    {
        $this->content = (string)$this->getContent();
        $images = $this->getImagesFromHTML($this->content);
        if ($this->dirPrefix) {
            Logger::log(sprintf('The %s has %s images', strtolower($this->dirPrefix), $total_images));
        } else {
            Logger::log(sprintf('This chapter has %s images', $total_images));
        }

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
