<?php
namespace Puleeno\Goader\Hosts\info;

use GuzzleHttp\Client;
use PHPHtmlParser\Dom;
use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Hook;
use Cocur\Slugify\Slugify;

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

    public function download($directoryName = null)
    {
        $this->dom = new Dom();
        if (!empty($directoryName)) {
            $this->dirPrefix = $directoryName;
        }
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

        foreach ($chapters as $chapter) {
            $chapter_downloader = new self($chapter['chapter_link']);
            $chapter_downloader->download($chapter['chapter_text']);
        }
    }

    public function downloadChapter()
    {
        $this->content = (string)$this->getContent();
        $this->dom->load($this->content);

        if (!empty($this->dirPrefix)) {
            $slugify = new Slugify();
            $chapterArr = explode('/', trim($this->host['path'], '/'));
            $chapter_name = end($chapterArr);
            $this->data['file_name_prefix'] = $slugify->slugify($chapter_name);
        }

        $images = $this->dom->find('.main_content_read img');

        if (count($images)) {
            $httpClient = new Client();
            foreach ($images as $image) {
                $image_url = $this->formatLink($image->getAttribute('src'));
                $fileName = $this->generateFileName($image_url);

                $this->getContent($image_url, $httpClient)->saveFile($fileName);
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
