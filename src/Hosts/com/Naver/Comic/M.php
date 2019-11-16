<?php
namespace Puleeno\Goader\Hosts\com\Naver\Comic;

use Puleeno\Goader\Hosts\com\Naver\Comic;

class M extends Comic
{
    public function checkPageType()
    {
        return 2;
    }

    public function getImagesFromDom()
    {
        $image_links = [];
        $images = $this->dom->find('#toonLayer ul li img');
        foreach ($images as $index => $imageHtml) {
            $image = $imageHtml->getAttribute('data-src');
            $image_links[] = $this->formatLink(trim($image));
        }
        return $image_links;
    }
}
