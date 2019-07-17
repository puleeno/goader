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
}
