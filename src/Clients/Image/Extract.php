<?php
namespace Puleeno\Goader\Clients\Image;

use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Logger;

class Extract
{
    const CONVERT_TOOL = 'convert';

    protected $options;

    protected $outputDir;
    protected $outputFormat;
    protected $excludes = [];

    protected $allowedTypeOutputs = ['jpg', 'jpeg', 'png', 'gif'];
    protected $currentIndex = 1;

    public function __construct()
    {
        $this->options = Command::getCommand()->getOptions();
        $this->allowedTypeOutputs = Hook::apply_filters(
            'goader_allowed_extract_outputs',
            $this->allowedTypeOutputs
        );

        $format = $this->options['format']->getValue();
        $this->outputFormat = empty($format) ? 'jpg' : $format;

        $outputDir = $this->options['output']->getValue();
        $this->outputDir = empty($outputDir) ? $this->defaultOutputDirectory() : $outputDir;

        $mode = $this->options['mode']->getValue();
        if (!empty($mode) && in_array($mode, array('vertical', 'horizontal', 'v', 'h'))) {
            $this->mode = $mode;
        } else {
            $this->mode = 'v';
        }
    }

    public function run()
    {
        if (!$this->validateFormat()) {
            Logger::log(sprintf('We do not support format %s', $this->outputFormat));
            return;
        }

        /**
         * Remove first options as your action
         */


        if (!file_exists($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        } elseif (is_file($this->outputDir)) {
            Logger::log('ERROR: Output directory is exists as a file!!');
            return;
        }

        $this->extract();
    }

    public function defaultOutputDirectory()
    {
        return sprintf(
            '%s/Extracted',
            Environment::getWorkDir()
        );
    }

    protected function validateFormat()
    {
        return in_array(
            $this->outputFormat,
            (array)$this->allowedTypeOutputs,
            true
        );
    }

    public function extract()
    {
        $extension = $this->selectExtension($this->getExtenions());

        $files = glob(
            sprintf(
                '*.{%s,%s}',
                $extension,
                strtoupper($extension)
            ),
            GLOB_BRACE
        );
        natsort($files);
        $layers = (int)$this->options['num']->getValue();

        foreach ($files as $file) {
            // $extension
            if ($extension == 'psd' || $extension == 'psb') {
                $this->extractPSD($file, $layers);
            } else {
                $fileName = sprintf('%s/%s', $this->outputDir, $this->currentIndex);
                $command = $this->buildCommand($file, $fileName);
                $this->executeCommand($command);
                $this->currentIndex++;
            }
        }
    }

    public function extractPSD($file, $layers = 0)
    {
        if ($layers <= 0) {
            $layers = 1000;
        }

        for ($i = 1; $i <= $layers; $i++) {
            $fileName = sprintf('%s/%s', $this->outputDir, $this->currentIndex);
            $command = $this->buildCommand($file . "[{$i}]", $fileName);
            $this->executeCommand($command);
            $this->currentIndex++;
        }
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

    public function getExtenions()
    {
        $extensions = [];
        $files = glob('*.*');
        foreach ($files as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $extension = strtolower($extension);
            if (!isset($extensions[$extension])) {
                $extensions[$extension] = 1;
            } else {
                $extensions[$extension]++;
            }
        }

        return $extensions;
    }

    public function buildCommand($input, $output)
    {
        if (is_array($input)) {
            $input = sprintf('"%s"', implode('" "', $input));
        }
        Logger::log();
        return sprintf(
            '%s %s "%s.%s"',
            self::CONVERT_TOOL,
            $input,
            $output,
            $this->outputFormat
        );
    }

    public function executeCommand($command)
    {
        shell_exec($command);
    }
}
