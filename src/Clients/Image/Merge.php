<?php
namespace Puleeno\Goader\Clients\Image;

use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Logger;

class Merge
{
    const CONVERT_TOOL = 'convert';

    protected $options;
    protected $action;

    protected $outputDir;
    protected $outputFormat;
    protected $excludes = [];

    protected $allowedTypeOutputs = ['jpg', 'jpeg', 'png', 'gif'];
    protected $mode = 'vertical';

    protected $currentIndex = 1;

    public function __construct()
    {
        $this->options = Command::getCommand()->getOptions();
        $this->allowedTypeOutputs = Hook::apply_filters(
            'goader_allowed_convert_outputs',
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

        $begin = $this->options['begin']->getValue();
        if ((int) $begin > 1) {
            $this->currentIndex = $begin;
        }
    }

    public function run()
    {
        if (!$this->validateFormat()) {
            Logger::log(sprintf('We do not support format %s', $this->outputFormat));
            return;
        }

        $separators = $this->convertOptionToListSeparator($this->options);

        /**
         * Remove first options as your action
         */
        $this->action = array_shift($separators);

        $separators = array_unique($separators);
        sort($separators, SORT_NATURAL);

        if (!file_exists($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        } elseif (is_file($this->outputDir)) {
            Logger::log('ERROR: Output directory is exists as a file!!');
            return;
        }

        if ($this->checkListIsIndexOrNot($separators)) {
            $this->convertByIndex($separators);
        } else {
            $this->convertByImageList($separators);
        }
    }

    public function defaultOutputDirectory()
    {
        return sprintf(
            '%s/Merged',
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

    protected function convertOptionToListSeparator($options)
    {
        $ret = [];
        foreach ($options as $key => $option) {
            if (!is_numeric($key)) {
                continue;
            }
            $ret[] = $option->getValue();
        }
        return $ret;
    }

    public function checkListIsIndexOrNot($separators)
    {
        foreach ((array)$separators as $val) {
            if (!is_numeric($val)) {
                return false;
            }
        }
        return true;
    }

    public function trunkFiles($files, $imageIndexes)
    {
        $totalFiles = count($files);
        if (empty($imageIndexes)) {
            $jum = (int)$this->options['num']->getValue();
            if (empty($jum)) {
                $jum = 1;
            }
            $current = 0;

            while ($current < $totalFiles) {
                $current = $jum + $current;
                $imageIndexes[] = $current;
            }
        }

        $trunkFiles = [];
        $currentIndex = 0;

        foreach ($imageIndexes as $index) {
            if ($index < 1) {
                continue;
            } elseif ($index > $totalFiles) {
                $index =  $totalFiles;
            }

            $items = $index - $currentIndex;
            $trunkFiles[] = array_slice($files, $currentIndex, $items);
            $currentIndex = $index;
        }

        if ($currentIndex < $totalFiles) {
            $trunkFiles[] = array_slice($files, $currentIndex, $totalFiles - $currentIndex);
        }
        return $trunkFiles;
    }

    public function convertByIndex($imageIndexes)
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

        $trunks = $this->trunkFiles(array_values($files), $imageIndexes);

        foreach ($trunks as $fileName => $files) {
            $fileName = sprintf('%s/%s', $this->outputDir, $this->currentIndex);

            $command = $this->buildCommand($files, $fileName);
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

    public function convertByImageList($imageList)
    {
        $outputFile = sprintf('%s/output', $this->outputDir);
        $command = $this->buildCommand($imageList, $outputFile);

        $this->executeCommand($command);
    }

    public function buildCommand($input, $output)
    {
        if (is_array($input)) {
            $input = sprintf('"%s"', implode('" "', $input));
        }
        Logger::log();
        return sprintf(
            '%s%s %s "%s.%s"',
            self::CONVERT_TOOL,
            $this->getModeCommand(),
            $input,
            $output,
            $this->outputFormat
        );
    }

    public function executeCommand($command)
    {
        passthru($command);
    }

    public function getModeCommand()
    {
        if (in_array($this->mode, array('v', 'vertical'))) {
            return ' -append';
        }
        return ' +append';
    }
}
