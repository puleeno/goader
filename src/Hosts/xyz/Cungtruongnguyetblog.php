<?php
namespace Puleeno\Goader\Hosts\xyz;

use GuzzleHttp\Client;
use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Logger;

class Cungtruongnguyetblog extends Host
{
    public function download($directoryName = null)
    {
        // https://cungtruongnguyetblog.xyz/luc-binh-lam-lang/
        $detectPatt = '/xyz\/(.+)-(chap|chapter|chuong)-(\d{1,})\/?$/';
        if (preg_match($detectPatt, $this->url, $matches)) {
            $this->data['file_name_prefix'] = sprintf('%s-chap-%d', $matches[1], $matches[3]);
            $this->downloadChapter();
        } else {
            exit(
                sprintf('Sorry! We do not support URL with this format %s', $this->url)
            );
        }
    }

    public function formatLink($originalLink)
    {
        return $originalLink;
    }

    public function downloadChapter()
    {
        $this->content = (string)$this->getContent();
        $this->dom->load($this->content);

        $images = $this->dom->find('.entry-content p img');
        $totalImages = count($images);

        Logger::log(sprintf(
            empty($this->data['chapter_title']) ?
                'This chapter has %d images' :
                'The ' . $this->data['chapter_title'] . ' has %d images',
            $totalImages
        ));

        if ($totalImages > 0) {
            $httpClient = new Client();
            foreach ($images as $index => $image) {
                $image_url = $this->formatLink($image->getAttribute('src'));
                $fileName = $this->generateFileName($image_url);
                Logger::log(sprintf('The image %s is downloading...', $index + 1));

                $this->getContent($image_url, $httpClient)->saveFile($fileName);
            }
        }
        Logger::log('The manga chapter is downloaded successfully');
    }

    public function downloadManga()
    {
        exit(
            sprintf('Currently, We do not support download full manga chapter for %s', $this->host['host'])
        );
    }
}
