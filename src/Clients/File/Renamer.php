<?php
namespace Puleeno\Goader\Clients\File;

use Cocur\Slugify\Slugify;
use Puleeno\Goader\Command;
use Puleeno\Goader\Environment;
use Puleeno\Goader\Hook;
use Puleeno\Goader\Logger;

class Renamer
{
    protected $options;
    protected $action;

    protected $prefix;
    protected $slugify;
    protected $currentIndex=1;
    protected $jumpStep=1;

    protected $allowedTypeOutputs = ['jpg', 'jpeg', 'png', 'gif'];

    public function __construct()
    {
        $this->slugify = new Slugify();

        $this->options = Command::getCommand()->getOptions();
        $this->allowedTypeOutputs = Hook::apply_filters(
            'goader_allowed_convert_outputs',
            $this->allowedTypeOutputs
        );

        $this->outputFormat = $this->options['format']->getValue();

        $outputDir = $this->options['output']->getValue();
        $this->outputDir = empty($outputDir) ? getcwd() : $outputDir;

        $begin = $this->options['begin']->getValue();
        if ((int) $begin > 1) {
            $this->currentIndex = $begin;
        }

        $jump = $this->options['jump']->getValue();
        if ((int) $jump > 1) {
            $this->jumpStep = (int)$jump;
        }
    }

    public function run()
    {
        $extension = $this->options['extension']->getValue();
        $this->prepare();
        if (empty($extension)) {
            $extension = $this->selectExtension(Environment::getExtenions());
        }
        $files = glob(
            sprintf('*.{%s}', preg_replace('/\s/', '', $extension)),
            GLOB_BRACE
        );

        natsort($files);
        $renamedFiles = [];
        $tmpDir = sprintf('%s/tmp', $this->outputDir);

        foreach ($files as $file) {
            $fileInfo = pathinfo($file);
            $fileName = $fileInfo['filename'];
            $format = $fileInfo['extension'];

            if (!empty($this->outputFormat)) {
                $format = $this->outputFormat;
            }
            if ($this->options['sequence']->getValue()) {
                $fileName = $this->currentIndex;
                $this->currentIndex = $this->currentIndex + $this->jumpStep;
            }

            if (!empty($prefix = $this->options['prefix']->getValue())) {
                $fileName = sprintf('%s-%s', $prefix, $fileName);
            }

            if (empty($this->options['raw']->getValue())) {
                $fileName = $this->slugify->slugify($fileName);
            }

            $newFileName = sprintf('%s/%s.%s', $tmpDir, $fileName, $format);
            if ($newFileName !== $file) {
                rename($file, $newFileName);
                $renamedFiles[] = $newFileName;
                $dirWriteLog = $fileInfo['dirname'] === '.' ? '' : $this->outputDir . '/';
                Logger::log(
                    sprintf('Rename file "%s" to "%s%s"', $file, $dirWriteLog, basename($newFileName))
                );
            }
        }
        $this->move($renamedFiles);
        $this->clean();
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

    public function prepare()
    {
        if (!file_exists($this->outputDir)) {
            mkdir($this->outputDir);
        }
        if (is_file($this->outputDir)) {
            exit(sprintf('%s is file', $this->outputDir));
        }

        $tmpDir = sprintf('%s/tmp', $this->outputDir);
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir);
        }
    }

    public function move($renamedFiles)
    {
        foreach ($renamedFiles as $file) {
            rename($file, sprintf('%s/%s', $this->outputDir, basename($file)));
        }
    }

    public function clean()
    {
        $tmpDir = sprintf('%s/tmp', $this->outputDir);
        foreach (glob(sprintf('%s/{*,.*}', $tmpDir), GLOB_BRACE) as $file) {
            unlink($file);
        }
        rmdir($tmpDir);
    }
}
