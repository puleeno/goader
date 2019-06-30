<?php
namespace Puleeno\Goader\Hosts\io;

use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;

class Json extends Host
{
    public function formatLink($originalLink)
    {
        $command = Command::getCommand();
        if ($command['host']) {
            $supportedHosts = Environment::supportedHosters();
            $host = new $supportedHosts[$command['host']]($originalLink, []);
            return $host->formatLink($originalLink);
        }
        $originalLink = Hook::apply_filters('json_link', $originalLink);
        $prefix = $command['url'] ? $command['url'] : '';

        return sprintf('%1$s%2$s', $prefix, $originalLink);
    }

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
