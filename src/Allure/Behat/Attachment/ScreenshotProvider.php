<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Allure\Behat\Attachment;

use Bex\Behat\ScreenshotExtension\Service\ScreenshotUploader;
use Bex\Behat\ScreenshotExtension\ServiceContainer\Config;
use FilesystemIterator;
use Symfony\Component\Console\Output\OutputInterface;
use Yandex\Allure\Adapter\Allure;
use Yandex\Allure\Adapter\Event\AddAttachmentEvent;
use Yandex\Allure\Adapter\Model\Provider;

class ScreenshotProvider extends ScreenshotUploader
{
    /** @var int */
    private $screenshotLimit;

    public function __construct(OutputInterface $output, Config $config, int $screenshotLimit)
    {
        parent::__construct($output, $config);
        $this->screenshotLimit = $screenshotLimit;
    }

    public function upload($screenshot, $fileName = 'failure.png')
    {
        parent::upload($screenshot, $fileName);

        if ($this->shouldAddAttachment()) {
            Allure::lifecycle()->fire(new AddAttachmentEvent($screenshot, 'Browser screenshot'));
        }
    }

    private function shouldAddAttachment(): bool
    {
        return $this->isOutputDirectoryConfigured() && $this->getNumberOfScreenshotsTaken() < $this->screenshotLimit;
    }

    private function getNumberOfScreenshotsTaken(): int
    {
        $iterator = new \GlobIterator(Provider::getOutputDirectory() . '/*.png', FilesystemIterator::KEY_AS_FILENAME);

        return iterator_count($iterator);
    }

    private function isOutputDirectoryConfigured(): bool
    {
        // PHPStorm uses its own formatter which does not configure Allure output path
        return Provider::getOutputDirectory() !== null;
    }
}
