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
        $this->nodeBinary = $this->getNodeBinary();
        $this->options = $options;
    }

    public function getNodeBinary()
    {
        if (empty($node = getenv('GLOADER_NODE_BINARY'))) {
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
            '%s "%s" %s',
            $this->getNodeBinary(),
            $this->getBinary(),
            ltrim($command)
        );
    }

    public function executeCommand($command)
    {
        var_dump($command);die;
        exec($command, $output);
        return implode("\n", $output);
    }

    public function request($method, $uri = '', $options = [])
    {
        $this->options = array_merge($this->options, $options);
        $this->options = array_merge($this->options, [
            'method' => $method,
            'uri' => $uri,
        ]);

        $command = $this->buildCommand($this->options);
        $body = $this->executeCommand($command);

        $res = new Response($body, 200);
        return $res;
    }

    public function setUserAgent($agent)
    {
        $this->options['user_agent'] = $agent;
    }
}
