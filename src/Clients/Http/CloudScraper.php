<?php
namespace Puleeno\Goader\Clients\Http;

class CloudScraper
{
    protected $binFile;
    protected $nodeBinary;

    public function __construct($options = array())
    {
        $this->bindFile = $this->getBinary();
        $this->nodeBinary = $this->getNodeBinary();
    }

    public function getNodeBinary()
    {
        if (empty($node = getnev('GLOADER_NODE_BINARY'))) {
            return 'node';
        }
        return $node;
    }

    private function getBinary()
    {
        return sprintf(
            '%s/goader.js',
            Environment::getGoaderDir()
        );
    }

    protected function buildCommand($commandArgs)
    {
        $command = '';

        foreach ($commandArgs as $key => $val) {
            if (is_string($key)) {
                $command .= sprintf(' --%s=%s', $key, $val);
            } else {
                $command .= ' ' . $val;
            }
        }
        return sprintf(
            '%s %s %s',
            $this->getNodeBinary(),
            $this->getBinary(),
            ltrim($command)
        );
    }

    public function executeCommand($command)
    {
        passthru($command);
    }

    public function request($method, $url)
    {
        $commandArgs = array();
        $command = $this->buildCommand($commandArgs);
        $this->executeCommand($command);
    }
}
