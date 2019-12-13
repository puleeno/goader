<?php
namespace Puleeno\Goader\Clients\Http;

use Puleeno\Goader\Clients\Http\Response;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Interfaces\Http\ClientInterface;

class CloudScraper implements ClientInterface
{
    protected $binFile;
    protected $nodeBinary;
    protected $options = [];
    protected $supportedOptions = [];

    public function __construct($options = [])
    {
        $this->binFile = $this->getBinary();
        $this->nodeBinary = Environment::getNodeBinary();
        $this->options = $options;
        $this->supportedOptions = Hook::apply_filters(
            'goader_cloudscraper_client_supported_options',
            array(
                'user-agent',
                'method',
                'cookies',
                'formdata',
                'saveto',
                'headers',
            )
        );
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
            $key = strtolower($key);
            if (!in_array($key, $this->supportedOptions)) {
                continue;
            }

            if ($key === 'headers') {
                $command .= sprintf(' --%s="%s"', $key, base64_encode(json_encode($val)));
                continue;
            }

            switch (gettype($val)) {
                case 'array':
                case 'object':
                    $val = json_encode($val);
                    break;
            }

            if (is_string($key)) {
                $command .= sprintf(' --%s="%s"', $key, str_replace('"', '\\"', $val));
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

    public function request($method, $uri = '', $options = [], $rawUri = false)
    {
        if (!$rawUri) {
            $url = parse_url($uri);
            $uri = sprintf('%s://%s/%s', $url['scheme'], $url['host'], urlencode(ltrim($url['path'], '/')));
        }

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
