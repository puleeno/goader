<?php

namespace Puleeno\Goader\Interfaces;

interface HostInterface
{
    public function download($directoryName = null);

    public function formatLink($originalLink);
}
