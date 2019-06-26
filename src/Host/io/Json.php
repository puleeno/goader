<?php
namespace Puleeno\Goader\Host\io;

use Puleeno\Goader\Abstracts\Host;

class Json extends Host
{
    public function download()
    {
        $images = $this->data['json'];
        if (!empty($images)) {
            foreach ($images as $image) {
                $image = $this->formatLink($image);
                $fileName = $this->generateFileName($image);
                $this->getContent($image)->saveFile($fileName);
            }
        }
    }
}
