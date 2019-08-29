<?php
namespace Puleeno\Goader\Hosts\com\Qq;

use GuzzleHttp\Client;
use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Logger;

class Ac extends Host
{
    const NAME = 'ac.qq';

    protected $dom;
    protected $useCloudScraper = true;

    protected function checkPageType()
    {
        /**
         * 1: Manga
         * 2: Chapter
         */
        $pat = '/(comicInfo|ComicView\/index)\/id\/(\d{1,})(\/cid\/(\d{1,}))?$/';
        if (preg_match($pat, $this->url, $matches)) {
            if ($matches[1] === 'comicInfo') {
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
        $dom_images = $this->dom->find('#comicContain li img');
        foreach($dom_images as $dom_image) {
            $images[] = $dom_image->getAttribute('src');
        }
        return $images;
    }


    public function downloadChapter()
    {
        $this->content = (string)$this->getContent();
        echo $this->content;die;

        $images = $this->getImagesFromHTML($this->content);
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
        $pre = Hook::apply_filters('ac_qq_filter_image_link', false, $link);
        if ($pre) {
            return $pre;
        }
        return $link;
    }
}
