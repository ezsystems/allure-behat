<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Allure\Behat\Test\Attachment;

use Allure\Behat\Attachment\ScreenshotProvider;
use Bex\Behat\ScreenshotExtension\Service\ScreenshotTaker;
use Bex\Behat\ScreenshotExtension\Service\ScreenshotUploader;
use Bex\Behat\ScreenshotExtension\ServiceContainer\Config;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Yandex\Allure\Adapter\Allure;
use Yandex\Allure\Adapter\Event\AddAttachmentEvent;
use Yandex\Allure\Adapter\Model\Provider;

class ScreenshotProviderTest extends TestCase
{

  public function testProvidesScreenshotAttachmentWhenConfigured()
  {
      // Given
      $outputInterface = $this
          ->getMockBuilder(OutputInterface::class)
          ->disableOriginalConstructor()
        ->getMock();

      $config = $this
        ->getMockBuilder(Config::class)
        ->disableOriginalConstructor()
        ->getMock();

      $config->expects($this->once())->method('getImageDrivers')->willReturn([]);
      $screenshotProvider = new ScreenshotProvider($outputInterface, $config, 1);
      Allure::setDefaultLifecycle();
      Provider::setOutputDirectory(sys_get_temp_dir());

      // When
      $screenshotProvider->upload('test');

      // Then
      Assert::assertInstanceOf(AddAttachmentEvent::class, Allure::lifecycle()->getLastEvent());
  }

  public function testDoesNothingWhenNotConfigured()
  {
    // Given
    $outputInterface = $this
      ->getMockBuilder(OutputInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $config = $this
      ->getMockBuilder(Config::class)
      ->disableOriginalConstructor()
      ->getMock();

    $config->expects($this->once())->method('getImageDrivers')->willReturn([]);
    $screenshotProvider = new ScreenshotProvider($outputInterface, $config, 1);
    Allure::setDefaultLifecycle();
    Provider::setOutputDirectory(null);

    // When
    $screenshotProvider->upload('test');

    // Then
    Assert::assertNull(Allure::lifecycle()->getLastEvent());
  }
}
