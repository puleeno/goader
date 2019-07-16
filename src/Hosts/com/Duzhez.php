<?php
namespace Puleeno\Goader\Hosts\com;

use Puleeno\Goader\Abstracts\Host;
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;

class Duzhez extends Host
{
    const NAME = 'duzhez';

    protected $html;
    protected $chapterID;
    protected $chapterPath;

    public function __construct($url, $host = null)
    {
        parent::__construct($url, $host);
    }

    public static function getCDNHost()
    {
        return Hook::apply_filters('duzhez_image_host', 'http://mhimg.9mmc.com:44237');
    }

    public function download($directoryName = null)
    {
        $command = Command::getCommand();
        if ($command['chapter']) {
            $this->chapterID = $command['chapter'];
        }

        if ($command['path']) {
            $this->chapterPath = $command['path'];
        }

        $this->html = (string)$this->getContent($this->url);

        if (preg_match('/chapterPath\s=\s\"([^\"]+)/', $this->html, $matches)) {
            $this->content = sprintf('goader download -h duzhez -s --path %s console.json', $matches[1]);
            $this->saveFile('command.txt');
        }

        // $this->downloadFirstImage();

        // $pages = $this->getAllImagePages();
        // foreach ($pages as $page) {
            // $this->downloadImageFromPage($page);
        // }
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

    public function formatLink($originalLink)
    {
        if (is_null($this->chapterPath)) {
            $command = Command::getCommand();
            if ($command['path']) {
                $this->chapterPath = $command['path'];
            } else {
                exit('Please input Duzhez chapter path to download images');
            }
        }
        $link = sprintf(
            '%s/%s/%s',
            self::getCDNHost(),
            $this->chapterPath,
            $originalLink
        );

        return Hook::apply_filters(self::NAME . '_format_link', $link, $this->chapterPath, $originalLink);
    }
}
