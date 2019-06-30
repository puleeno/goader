<?php
namespace Puleeno\Goader\Hosts\info;

use PHPHtmlParser\Dom;
use Puleeno\Goader\Abstracts\Host;
use GuzzleHttp\Client;
use Puleeno\Goader\Hook;

class TruyenDoc extends Host
{
    protected $dom;

    public $mangaID;
    public $chapterID;

    protected $useCookie = true;

    protected function checkPageType()
    {
        /**
         * Manga URL: http://truyendoc.info/10498/bj-alex
         * Chapter URI: http://truyendoc.info/10498/266534/bj-alex-chap-1/
         *
         * 1: Manga
         * 2: Chapter
         */
        $pat = '/truyendoc\.info\/(\d{1,})(\/\d{1,})?\/(.+)\/?$/';
        if (preg_match($pat, $this->url, $matches)) {
            $this->mangaID = $matches[1];
            if (empty($matches[2])) {
                return 1;
            }
            $this->mangaID = $matches[2];
            return 2;
        }
        return false;
    }

    public function download()
    {
        $this->dom = new Dom();

        $page = $this->checkPageType();
        if ($page === false) {
            $this->dontSupport();
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
    }

    public function downloadChapter($folderName = null)
    {
        $this->content = $this->getContent($this->url);
        $this->dom->load($this->content);

        $images = $this->dom->find('.main_content_read img');

        if (count($images)) {
            $httpClient = new Client();
            foreach ($images as $image) {
                $image_url = $this->formatLink($image->getAttribute('src'));

                $fileName = $this->generateFileName($image_url);
                $httpClient->get($image_url, [
                    'save_to' => $fileName
                ]);
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
