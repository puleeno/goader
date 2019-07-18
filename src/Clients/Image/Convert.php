<?php
namespace Puleeno\Goader\Clients\Image;

use Puleeno\Goader\Command;

class Convert
{
    protected $options;
    protected $action;

    public function __construct()
    {
        $this->options = Command::getCommand()->getOptions();
        $this->action =array_shift($this->command);
    }

    public function run()
    {
        var_dump((array)$this->options);
        die;
    }
}
