<?php
namespace Puleeno\Goader\Clients\Http;

use Puleeno\Goader\Clients\Http\Response;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Interfaces\Http\ClientInterface;

class CloudScraper implements ClientInterface
{
    protected $binFile;
    protected $nodeBinary;
    protected $options = [];

    public function __construct($options = [])
    {
        $this->binFile = $this->getBinary();
        $this->nodeBinary = Environment::getNodeBinary();
        $this->options = $options;
    }

    private function getBinary()
    {
        return sprintf(
            '%s/goader.js',
            Environment::getGoaderDir()
        );
    }

    protected function buildCommand($uri, $commandArgs)
    {
        $command = '';
        foreach ($commandArgs as $key => $val) {
            switch (gettype($val)) {
                case 'array':
                case 'object':
                    $val = '\'' . json_encode($val) . '\'';
                    break;
            }

            if (is_string($key)) {
                $command .= sprintf(' --%s="%s"', $key, $val);
            } else {
                $command .= ' ' . $val;
            }
        }
        return sprintf(
            '%s "%s" request %s "%s"',
            Environment::getNodeBinary(),
            $this->getBinary(),
            ltrim($command),
            $uri
        );
    }

    public function executeCommand($command)
    {
        exec($command, $output);
        return implode("\n", $output);
    }

    public function request($method, $uri = '', $options = [])
    {
        $this->options = array_merge($this->options, $options);
        $this->options = array_merge($this->options, [
            'method' => $method,
        ]);

        $command = $this->buildCommand($uri, $this->options);
        $body    = $this->executeCommand($command);

        $res = new Response($body, 200);
        return $res;
    }

    public function setUserAgent($agent)
    {
        $this->options['user_agent'] = $agent;
    }
}
