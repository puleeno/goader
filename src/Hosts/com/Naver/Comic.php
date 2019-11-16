<?php
namespace Puleeno\Goader\Hosts\com\Naver;

use GuzzleHttp\Client;
use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Logger;
use Puleeno\Goader\Hook;

class Comic extends Host
{
    const NAME = 'naver';

    public function download($directoryName = null)
    {
        if (!($pageType = $this->checkPageType())) {
            exit('Does not support this URL');
        }
        $this->getContent();
        $this->dom->load($this->content);
        if ($pageType == 2) {
            $this->downloadChapter();
        }
    }

    protected function checkPageType()
    {
        return 2;
    }

    public function getImagesFromDom()
    {
        $image_links = [];
        $images = $this->dom->find('#comic_view_area img');
        foreach ($images as $index => $imageHtml) {
            $image = $imageHtml->getAttribute('src');
            $image_links[] = $this->formatLink(trim($image));
        }
        return $image_links;
    }

    public function downloadChapter()
    {
        $images = $this->getImagesFromDom();
        if (empty($images)) {
            exit('The goader do not found any images');
        }
        $image_host = parse_url($images[0]);
        $httpClient = new Client(array_merge(
            $this->defaultHttpClientOptions(),
            array(
                'headers' => array(
                    ':authority' => $image_host['host'],
                    'allow_redirects' => true,
                    'cache-control' => 'max-age=0',
                    'upgrade-insecure-requests' => 1,
                    'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.97 Safari/537.36',
                    'sec-fetch-user' => '?1',
                    'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
                    'sec-fetch-site' => 'none',
                    'sec-fetch-mode' => 'navigate',
                    'accept-encoding' => 'gzip, deflate, br',
                    'accept-language' => 'en-US,en;q=0.9,vi;q=0.8'
                )
            )
        ));
        foreach ($images as $index => $image) {
            try {
                if (!$this->validateLink($image)) {
                    Logger::log(sprintf('The url #%d is invalid with value "%s"', $index + 1, $image));
                    continue;
                }

                Environment::setCurrentIndex($index + 1);
                Logger::log(sprintf('The image %s with URL %s is downloading', $index + 1, $image));

                $fileName = $this->generateFileName($image, false);
                $this->getContent($image, $httpClient)->saveFile($fileName);
            } catch (\Exception $e) {
                Logger::log($e->getMessage());
            }
        }

        Logger::log('All your URL has downloaded sucessfully!!');
    }

    public function formatLink($originalUrl)
    {
        $link = $originalUrl;
        $pre = Hook::apply_filters('naver_filter_image_link', false, $link);
        if ($pre) {
            return $pre;
        }
        return $link;
    }
}
