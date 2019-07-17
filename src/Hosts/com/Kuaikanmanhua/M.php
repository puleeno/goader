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
}
