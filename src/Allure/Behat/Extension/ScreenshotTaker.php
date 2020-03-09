<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Allure\Behat\Extension;

use Bex\Behat\ScreenshotExtension\Service\ScreenshotTaker as BaseScreenshotTaker;
use Yandex\Allure\Adapter\Allure;
use Yandex\Allure\Adapter\Event\AddAttachmentEvent;

class ScreenshotTaker extends BaseScreenshotTaker
{
    private $baseScreenshotTaker;

    public function __construct(BaseScreenshotTaker $baseScreenshotTaker)
    {
      $this->baseScreenshotTaker = $baseScreenshotTaker;
    }

  public function takeScreenshot(): void
    {
        $this->baseScreenshotTaker->takeScreenshot();
        $image = $this->baseScreenshotTaker->getImage();
        Allure::lifecycle()->fire(new AddAttachmentEvent($image, 'Browser screenshot'));
    }
}
