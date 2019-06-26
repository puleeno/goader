<?php
namespace Puleeno\Goader\Host\com;

use Puleeno\Goader\Environment;
use Puleeno\Goader\Abstracts\Host;

class Duzhez extends Host
{
    protected $html;

    public function download()
    {
        $this->html = (string)$this->getContent($this->url);

        $this->downloadFirstImage();

        $pages = $this->getAllImagePages();
        foreach ($pages as $page) {
            $this->downloadImageFromPage($page);
        }
    }

    public function imageFromHTML($html)
    {
        
    }

    public function downloadFirstImage()
    {
        $imageURL = $this->imageFromHTML($this->html);
        $image = $this->getContent($imageURL);
        $fileName = sprintf('%s.%s', $this->generateFileName(), $this->detectExtension($imageURL));
        $image->saveFile($fileName);
    }

    public function getAllImagePages()
    {
    }

    public function downloadImageFromPage($pageIndex)
    {
    }
}