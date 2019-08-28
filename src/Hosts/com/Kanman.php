<?php
namespace Puleeno\Goader\Hosts\com;

use GuzzleHttp\Client;
use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Logger;
use Peast\Peast;
use Peast\Renderer;
use Peast\Formatter\PrettyPrint;
use Puleeno\Goader\Clients\Downloader\Wget;

class Kanman extends Host
{
    const NAME = 'kanman';

    protected $useCloudScraper = true;

    protected $dom;
    public $mangaID;
    public $chapterID;

    protected function checkPageType()
    {
        /**
         * 1: Manga
         * 2: Chapter
         */
        $pat = '/\.com\/([^\/]*)\/?([^\.]*)?(\.html)?$/';
        if (preg_match($pat, $this->url, $matches)) {
            if (!isset($matches[3])) {
                $this->mangaID = $matches[2];
                return 1;
            }
            $this->chapterID = $matches[2];
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

    protected function makeChapterNum($text)
    {
        if (preg_match('/(\d{1,})/', $text, $matches)) {
            return sprintf('Chap %s', $matches[1]);
        }
        return $text;
    }

    public function downloadManga()
    {
        $this->content = (string)$this->getContent();
        $this->dom->load($this->content);
        $domChapters = $this->dom->find('#j_chapter_list li a');
        $chapters = array();
        foreach ($domChapters as $chapter) {
            $chapters[] = array(
                'chapter_link' => sprintf('%s://%s%s', $this->host['scheme'], $this->host['host'], $chapter->getAttribute('href')),
                'chapter_text' => $this->makeChapterNum($chapter->find('p.name')->text)
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

    public function preparingJsonStr($str)
    {
        $json = $str;
        $pos = strpos($str, 'return');
        $searchPatterns = array();
        $replaces = array();

        if (is_numeric($pos)) {
            $json = substr($str, 0, $pos);
            $json = rtrim($json, ';');
            $json .= '}';
            $searchPatterns = array(
                '/\w\[(\d{1,})\]="/',
                '/";/'
            );
            $replaces = array(
                '"$1": "',
                '",'
            );
        } else {
            $searchPatterns = array(
                '/(width|height):[^,]*,\n?/',
                '/(url)/'
            );
            $replaces = array('', '"$1"');
        }

        $json = preg_replace($searchPatterns, $replaces, $json);
        return $json;
    }

    public function getImagesFromComicInfo($comicInfo)
    {
        $images = array();

        if (!empty($comicInfo)) {
            $domain = sprintf('mhpic.%s', $comicInfo->current_chapter->chapter_domain);
            $startFrom = $comicInfo->current_chapter->start_num;
            $totalChapterImages = $comicInfo->current_chapter->end_num;
            $format = str_replace('$$', '%s', $comicInfo->current_chapter->rule) . '%s';
            for ($i=$startFrom; $i<= $totalChapterImages; $i++) {
                $fileName = sprintf($format, $i, '-noresize');
                $images[] = sprintf('%s://%s%s', $this->host['scheme'], $domain, $fileName);
            }
        }

        return $images;
    }

    public function getChapterImagePattern()
    {
        return '/comicInfo=(.+\}\})/';
    }

    public function downloadChapter()
    {
        $this->content = (string)$this->getContent();
        if (!preg_match($this->getChapterImagePattern(), $this->content, $matches)) {
            Logger::log(sprintf('Occur error when download chapter has URL %s', $this->url));
        }
        $ast = Peast::latest($matches[0])->parse();
        $renderer = new Renderer;
        $renderer->setFormatter(new PrettyPrint);
        $pretty = $renderer->render($ast);
        $pretty = preg_replace(
            array(
                '/([^\s]*):/',
                '/comicInfo \= /',
                '/\};/',
                '/\"(.+)(time\":)(.+)\n/',
            ),
            array(
                '"$1":',
                '',
                '}',
                '',
            ),
            $pretty
        );

        $chapterInfo = json_decode($pretty);

        $images = $this->getImagesFromComicInfo($chapterInfo);
        $total_images = count($images);
        if ($this->dirPrefix) {
            Logger::log(sprintf('The %s has %s images', strtolower($this->dirPrefix), $total_images));
        } else {
            Logger::log(sprintf('This chapter has %s images', $total_images));
        }

        if ($total_images > 0) {
            $downloader = new Wget();
            foreach ($images as $index => $image) {
                Environment::setCurrentIndex($index + 1);
                try {
                    Logger::log(sprintf('Downloading image #%d has URL %s.', $index + 1, $image));
                    $image_url = $this->formatLink($image);
                    if (!$this->validateLink($image_url)) {
                        Logger::log(sprintf('The url #%d is invalid with value "%s"', $index + 1, $image_url));
                        continue;
                    }

                    $fileName = $this->generateFileName($image_url);
                    $downloader->getContent($image_url, $fileName);
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
        $pre = Hook::apply_filters('u17_filter_image_link', false, $link);
        if ($pre) {
            return $pre;
        }
        return $link;
    }
}
