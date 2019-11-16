<?php
namespace Puleeno\Goader\Hosts\com\Naver;

use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Clients\Downloader\Wget;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Logger;

class Comic extends Host {
    const NAME = 'naver';

    public function download($directoryName = null) {
        if(!($pageType = $this->checkPageType())) {
            exit('Does not support this URL');
        }
        $this->getContent();
        $this->dom->load($this->content);
        if($pageType == 2) {
            $this->downloadChapter();
        }
    }

    protected function checkPageType() {
        return 2;
    }

    public function getImagesFromDom() {
        $image_links = [];
        $images = $this->dom->find('#comic_view_area img');
        foreach ($images as $index => $imageHtml) {
            $image = $imageHtml->getAttribute('src');
            $image_links[] = $this->formatLink(trim($image));
        }
        return $image_links;
    }

    public function downloadChapter() {
        $downloader = new Wget();
        foreach ($this->getImagesFromDom() as $index => $image) {
            try {
                if (!$this->validateLink($image)) {
                    Logger::log(sprintf('The url #%d is invalid with value "%s"', $index + 1, $image));
                    continue;
                }

                Environment::setCurrentIndex($index + 1);
                Logger::log(sprintf('The image %s with URL %s is downloading...', $index + 1, $image));

                $fileName = $this->generateFileName($image, false);
                $downloader->getContent($image, $fileName);
            } catch (\Exception $e) {
                Logger::log($e->getMessage());
            }
        }

        Logger::log('All your URL has downloaded sucessfully!!');
    }

    public function formatLink($url) {
        return $url;
    }
}

