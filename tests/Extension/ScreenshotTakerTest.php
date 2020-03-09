<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Allure\Behat\Test\Extension;

use Allure\Behat\Extension\ScreenshotTaker;
use Bex\Behat\ScreenshotExtension\Service\ScreenshotTaker as BaseScreenshotTaker;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Yandex\Allure\Adapter\Allure;
use Yandex\Allure\Adapter\Event\AddAttachmentEvent;

class ScreenshotTakerTest extends TestCase
{

  public function testTakeScreenshot()
  {
      $baseScreenshotTaker = $this
        ->getMockBuilder(BaseScreenshotTaker::class)
        ->disableOriginalConstructor()
        ->getMock();

      $baseScreenshotTaker->expects($this->once())->method('takeScreenshot');
      $screenshotTaker = new ScreenshotTaker($baseScreenshotTaker);

      $screenshotTaker->takeScreenshot();

      Assert::assertInstanceOf(AddAttachmentEvent::class, Allure::lifecycle()->getLastEvent());
  }
}
