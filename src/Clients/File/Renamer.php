<?php
namespace Puleeno\Goader\Clients\File;

use Puleeno\Goader\Command;

class Renamer
{
    protected $options;
    protected $action;

    protected $prefix;
    protected $currentIndex=1;
    protected $jumpStep=1;

    public function __construct()
    {
        // $this->options = Command::getCommand()->getOptions();
        // $this->action =array_shift($this->options);
    }

    public function run()
    {
    }

    public function selectExtension($extensions)
    {
        $max = 0;
        $extension;
        foreach ($extensions as $index => $itemCount) {
            if ($itemCount > $max) {
                $max = $itemCount;
                $extension = $index;
            }
        }
        return $extension;
    }
}
