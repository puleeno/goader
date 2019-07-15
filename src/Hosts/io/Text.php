<?php
namespace Puleeno\Goader\Hosts\io;

use GuzzleHttp\Client;
use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;

class Text extends Host
{
    public function formatLink($originalLink)
    {
        $command = Command::getCommand();
        if ($command['host']) {
            $supportedHosts = Environment::supportedHosters();
            $host = new $supportedHosts[$command['host']]($originalLink, []);
            return $host->formatLink($originalLink);
        }
        $originalLink = Hook::apply_filters('text_link', $originalLink);
        $prefix = $command['url'] ? $command['url'] : '';

        return sprintf('%1$s%2$s', $prefix, $originalLink);
    }

    public function download($directoryName = null)
    {
        $images = explode("\n", file_get_contents($this->host['path']));
        if (!empty($images)) {
            $httpClient = new Client();
            foreach ($images as $image) {
                $image = $this->formatLink($image);
                $fileName = $this->generateFileName($image);
                $this->getContent($image, $httpClient)->saveFile($fileName);
            }
        }
    }
}
