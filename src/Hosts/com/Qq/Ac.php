<?php
namespace Puleeno\Goader\Hosts\com\Qq;

use Puleeno\Goader\Abstracts\Host;

class Ac extends Host
{
    const NAME = 'ac.qq';

    public function download($directoryName = null)
    {
        echo 'prepare support';
    }

    public function formatLink($originUrl)
    {
    }
}
