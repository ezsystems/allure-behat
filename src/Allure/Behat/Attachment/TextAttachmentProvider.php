<?php

namespace Allure\Behat\Attachment;

use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Testwork\Tester\Result\TestResult;
use EzSystems\Behat\Core\Log\TestLogProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Yandex\Allure\Adapter\Allure;
use Yandex\Allure\Adapter\Event\AddAttachmentEvent;
use Behat\Mink\Mink;
use Yandex\Allure\Adapter\Model\Provider;

class TextAttachmentProvider implements EventSubscriberInterface
{
    /**
     * @var \Behat\Mink\Mink
     */
    private $mink;

    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    private $kernel;

    public function __construct(Mink $mink, KernelInterface $kernel)
    {
        $this->mink = $mink;
        $this->kernel = $kernel;
    }

    public static function getSubscribedEvents()
    {
        return [
          StepTested::AFTER => 'provideLogsOnError',
        ];
    }

    public function provideLogsOnError(AfterStepTested $event)
    {
        if ($event->getTestResult()->getResultCode() !== TestResult::FAILED) {
            return;
        }

        $testLogProvider = new TestLogProvider($this->mink->getSession(), $this->kernel->getLogDir());
        $applicationsLogs = $testLogProvider->getApplicationLogs();
        $browserLogs = $testLogProvider->getBrowserLogs();

        if ($this->isOutputDirectoryConfigured()) {
            Allure::lifecycle()->fire(new AddAttachmentEvent($this->formatForDisplay($browserLogs), 'JS console logs'));
            Allure::lifecycle()->fire(new AddAttachmentEvent($this->formatForDisplay($applicationsLogs), 'Application logs'));
        }
    }

    private function formatForDisplay(array $entries)
    {
        return implode(PHP_EOL, $entries);
    }

    private function isOutputDirectoryConfigured(): bool
    {
        // PHPStorm uses its own formatter which does not configure Allure output path
        return Provider::getOutputDirectory() !== null;
    }
}
