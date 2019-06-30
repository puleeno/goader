<?php
namespace Puleeno\Goader\Hosts\xyz;

use GuzzleHttp\Client;
use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Logger;

class Cungtruongnguyetblog extends Host
{
    protected $chapterPatterns = array('chap', 'chapter', 'chuong');

    public function download($directoryName = null)
    {
        $detectPatt = sprintf('/xyz\/(.+)-(%s)-(\d{1,})\/?$/', implode('|', $this->chapterPatterns));
        if (preg_match($detectPatt, $this->url, $matches)) {
            $this->data['file_name_prefix'] = sprintf('%s-chap-%d', $matches[1], $matches[3]);
            $this->downloadChapter();
        } else {
            $isManga = false;
            foreach ($this->chapterPatterns as $pattern) {
                $client = new Client();
                $try_uri = sprintf('%s-%s-1', $this->url, $pattern);
                $response = $client->head($try_uri);
                if ($response->getStatusCode()> 199 && $response->getStatusCode() < 300) {
                    $isManga = true;
                    break;
                }
            }
            if ($isManga) {
                $this->downloadManga($pattern);
            } else {
                exit(
                    sprintf('Sorry! We do not support URL with this format %s', $this->url)
                );
            }
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

    public function downloadManga($pattern)
    {
        $currentChapter = 1;
        exit(
            sprintf('Currently, We do not support download full manga chapter for %s', $this->host['host'])
        );
    }
}
