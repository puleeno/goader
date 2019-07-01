<?php
namespace Puleeno\Goader\Hosts\com;

use Puleeno\Goader\Abstracts\Host;

class Truyentranh869 extends Host
{
    protected $chapterPatterns = array('chap', 'chapter');
    public function download($folderName = null)
    {
        // http://truyentranh869.com/oh-my/
        // http://truyentranh869.com/oh-my-chap-5
        $detectPattern = sprintf('/com\/(.+-()-\d{1,})\/?$/', implode('|', $this->chapterPatterns));

        if (preg_match($detectPattern, $this->url, $matches)) {
            var_dump($matches);
            $this->downloadChapter();
        } else {
        }
    }

    public function formatLink($originalUrl)
    {
    }

    public function downloadManga()
    {
    }

    public function downloadChapter()
    {
        $this->content = $this->getContent();
        var_dump((string)$this->content);
    }
}
