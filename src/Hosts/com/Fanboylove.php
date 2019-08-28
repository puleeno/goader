<?php
namespace Puleeno\Goader\Hosts\com;

use Cocur\Slugify\Slugify;
use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Clients\Downloader\Wget;
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
    protected $useCookieJar = true;
    protected $supportLogin = true;

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
        $this->content = (string)$this->getContent();
        $this->dom->load($this->content);

        $chapterArr = explode('/', trim($this->host['path'], '/'));
        $chapter_name = end($chapterArr);

        if ($chapter_name) {
            $slugify = new Slugify();
            $this->data['file_name_prefix'] = $slugify->slugify($chapter_name);
        }

        $images = $this->dom->find('.reading-content img');
        $total_images = count($images);
        if ($this->dirPrefix) {
            Logger::log(sprintf('The %s has %s images', strtolower($this->dirPrefix), $total_images));
        } else {
            Logger::log(sprintf('This chapter has %s images', $total_images));
        }


        if ($total_images > 0) {
            $headers = [
                'Referer'   => $this->url,
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A356 Safari/604.1',
            ];
            $downloader = new Wget([
                'headers' => $headers,
            ]);
            $currentIndex = Environment::getCurrentIndex();
            foreach ($images as $index => $image) {
                try {
                    Logger::log(sprintf('The image %s is downloading...', $index + 1));
                    $image_url = $this->formatLink($image->getAttribute('data-src'));
                    if (empty($image_url)) {
                        continue;
                    }
                    $fileName = $this->generateFileName($image_url);
                    $downloader->getContent($image_url, $fileName);
                    Environment::setCurrentIndex(++$currentIndex);
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
