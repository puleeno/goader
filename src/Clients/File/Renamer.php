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
        $extension = $this->selectExtension(Environment::getExtenions());
        $files = glob(sprintf('*.%s', $extension));
        natsort($files);

        foreach($files as $file) {
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

            $newFileName = sprintf('%s/%s.%s', $fileInfo['dirname'], $fileName, $format);

            if ($newFileName !== $file) {
                rename($file, $newFileName);
                Logger::log(
                    sprintf('Rename file "%s" to "%s.%s"', $file, $fileName, $format)
                );
            }
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
}
