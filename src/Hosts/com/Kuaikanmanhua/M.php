<?php
namespace Puleeno\Goader\Hosts\com\Kuaikanmanhua;

use Puleeno\Goader\Hosts\com\Kuaikanmanhua;

class M extends Kuaikanmanhua
{
    protected function checkPageType()
    {
        /**
         * Manga URL: https://m.kuaikanmanhua.com/mobile/2631/list
         * Chapter URI: https://m.kuaikanmanhua.com/mobile/comics/141851
         *
         * 1: Manga
         * 2: Chapter
         */
        $pat = '/mobile\/(comics|\d{1,})\/(.+)/';
        if (preg_match($pat, $this->url, $matches)) {
            if (is_int($matches[1])) {
                $this->mangaID = $matches[1];
                return 1;
            }
            $this->chapterID = $matches[2];
            return 2;
        }
        return false;
    }

    public function getImagesFromJsonStr($strJson)
    {
        $strJson = $this->preparingJsonStr($strJson);
        $json = json_decode($strJson, true);
        if (count($json) < 1) {
            return [];
        }
        return $json;
    }

    public function getChapterImagePattern()
    {
        return '/__NUXT__=\(function\([^\)]*\)(\{[^\}]*\})/';
    }

    public function downloadManga()
    {
        $this->content = (string)$this->getContent();
        $this->dom->load($this->content);

        $domChapters = $this->dom->find('ul#chapter a');
        $chapters = array();
        foreach ($domChapters as $chapter) {
            $chapters[] = array(
                'chapter_link' => $chapter->getAttribute('href'),
                'chapter_text' => $this->makeChapterNum($chapter->text)
            );
        }

        Logger::log(sprintf('This manga has %d chapters', count($chapters)));
        Logger::log('Downloading...');

        foreach ($chapters as $chapter) {
            $chapter_downloader = new self($chapter['chapter_link'], $this->host);
            $chapter_downloader->download($chapter['chapter_text']);
        }
        Logger::log('The manga is downloaded successfully!!');
    }
}
