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
    const AC_QQ_KEY = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';

    protected $dom;

    protected function checkPageType()
    {
        /**
         * 1: Manga
         * 2: Chapter
         */
        $pat = '/(comicInfo|ComicView\/index)\/id\/(\d{1,})(\/cid\/(\d{1,}))?/';
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
        $data = '';
        $nonce = '';
        if (preg_match('/var\sDATA\s{1,}=\s\'([^\']*)/', $content, $matches)) {
            $data = $matches[1];
        }
        if (preg_match('/window.nonce\s=\s([^;]*)/', $content, $matches)) {
            $nonce = $matches[1];
            $nonce = preg_replace('/[^\w]/', '', $nonce);
        }

        $h = fopen('code.html', 'w');
        fwrite($h, $content);
        fclose($h);


        if (empty($data) || empty($nonce)) {
            exit('Invalid data on ac.qq.com. Please contact creator for update the host.');
        }

        $str = $data;
        $N = $nonce;
        preg_match_all('/./', $str, $maches);
        $T = $maches[0];
        $len;
        $locate;
        $str = '';
        if (preg_match_all('/\d+[a-zA-Z]+/', $N, $maches)) {
            $N = $maches[0];
        }
        $len = count($N);
        while ($len--) {
            $locate = ((int)$N[$len]) & 255;
            $str = preg_replace('/\d+/', '', $N[$len]);
            array_splice($T, $locate, strlen($str));
        }
        $T = implode('', $T);

        var_dump($json);
        die;

        $images = [];
        $this->dom->load($content);
        $dom_images = $this->dom->find('#comicContain li img');
        foreach ($dom_images as $dom_image) {
            $images[] = $dom_image->getAttribute('src');
        }
        return $images;
    }


    public function downloadChapter()
    {
        $this->content = (string)$this->getContent();
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

    function _utf8_decode($c)
    {
        $a = "";
        $d = $c1 = $c2 = 0;
        for ($b = 0; $b < strlen($c);) {
            die($c);
            $d = ord($c{$b});
            if (128 > $d) {
                $a += chr($d);
                $b++;
            } else {
                if (191 < $d && 224 > $d) {
                    $c2 = $c{$b + 1};
                    $a += chr(($d & 31) << 6 | $c2 & 63);
                    $b += 2;
                } else {
                    $c2 = ord($c{$b + 1});
                    $c3 = ord($c{$b + 2});
                    $a += chr(($d & 15) << 12 | ($c2 & 63) << 6 | $c3 & 63);
                    $b += 3;
                };
            }
        }
        return $a;
    }

    function _decode($c)
    {
        $_keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
        $a = "";
        $b;
        $d;
        $h;
        $f;
        $g;
        $e = 0;

        // console.log(c.replace(/[^A-Za-z0-9\+\/\=]/g));

        $c = preg_replace('/[^A-Za-z0-9\+\/\=]/', '', $c);

        for ($c; $e < strlen($c);) {
            $char1 = $c{$e++};
            $b = strpos($_keyStr, $char1);

            $char2 = $c{$e++};
            $d = strpos($_keyStr, $char2);

            $char3 = $c{$e++};
            $f = strpos($_keyStr, $char3);

            $char4 = $c{$e++};
            $g = strpos($_keyStr, $char4);

            $b = $b << 2 | $d >> 4;
            $d = ($d & 15) << 4 | $f >> 2;
            $h = ($f & 3) << 6 | $g;

            $a .= chr($b);
            if (64 != $f) {
                $a .= chr($d);
            }
            if (64 != $g) {
                $a .= chr($h);
            }
        }
        return $a = $this->_utf8_decode($a);
    }
}
