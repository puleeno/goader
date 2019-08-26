<?php
namespace Puleeno\Goader\Hosts\com;

use Cocur\Slugify\Slugify;
use GuzzleHttp\Client;
use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Logger;

class Fanboylove extends Host
{
    const NAME = 'fanboylove';

    protected $dom;

    public $mangaID;
    public $chapterID;

    protected $useCloudScraper = true;
    protected $useCookie = true;

    protected function checkPageType()
    {
    }

    public function download($directoryName = null)
    {
        if (!empty($directoryName)) {
            $this->dirPrefix = $directoryName;
        }
        $this->downloadManga();
    }

    public function downloadManga()
    {
        $this->content = (string)$this->getContent();
        $this->dom->load($this->content);

        $domChapters = $this->dom->find('.list_chapter_comic ul.list_chapter a');
        $chapters = array();
        foreach ($domChapters as $chapter) {
            $chapters[] = array(
                'chapter_link' => sprintf(
                    '%1$s://%2$s%3$s',
                    $this->host['scheme'],
                    $this->host['host'],
                    $chapter->getAttribute('href')
                ),
                'chapter_text' => $chapter->text
            );
        }
        $chapters = array_reverse($chapters);

        Logger::log(sprintf('This manga has %d chapters', count($chapters)));
        Logger::log('Downloading...');

        foreach ($chapters as $chapter) {
            // Reset current index to 1 to start download the new chapter
            Environment::setCurrentIndex(1);

            $chapter_downloader = new self($chapter['chapter_link']);
            $chapter_downloader->download($chapter['chapter_text']);
        }
        Logger::log('The manga is downloaded successfully!!');
    }

    public function downloadChapter()
    {
        $this->content = (string)$this->getContent();
        $this->dom->load($this->content);

        $chapterArr = explode('/', trim($this->host['path'], '/'));
        $chapter_name = end($chapterArr);

        if ($chapter_name) {
            $slugify = new Slugify();
            $this->data['file_name_prefix'] = $slugify->slugify($chapter_name);
        }

        $images = $this->dom->find('.main_content_read img');
        $total_images = count($images);
        if ($this->dirPrefix) {
            Logger::log(sprintf('The %s has %s images', strtolower($this->dirPrefix), $total_images));
        } else {
            Logger::log(sprintf('This chapter has %s images', $total_images));
        }


        if ($total_images > 0) {
            $httpClient = new Client();
            foreach ($images as $index => $image) {
                try {
                    Logger::log(sprintf('The image %s is downloading...', $index + 1));
                    $image_url = $this->formatLink($image->getAttribute('src'));
                    $fileName = $this->generateFileName($image_url);
                    $this->getContent($image_url, $httpClient)->saveFile($fileName);
                } catch (\Exception $e) {
                    Logger::log($e->getMessage());
                }
            }
            if ($this->dirPrefix) {
                Logger::log(sprintf('The %s is downloaded', strtolower($this->dirPrefix)));
            } else {
                Logger::log(sprintf('The chapter is downloaded successfully!!'));
            }
            unset($images);
        }
    }

    public function formatLink($originalUrl)
    {
        $link = $originalUrl;

        $pre = Hook::apply_filters('truyendoc_filter_image_link', false, $link);
        if ($pre) {
            return $pre;
        }

        if (preg_match('/url=(https?.+.\w{1,4})/', $link, $matches)) {
            $link = $matches[1];
        }
        return $link;
    }
}