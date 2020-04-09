<?php
/**
 * Copyright (c) 2016 Eduard Sukharev
 * Copyright (c) 2018 Tiko Lakin.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * See LICENSE.md for full license text.
 */
namespace Allure\Behat\Formatter;

use Allure\Behat\Exception\ArtifactExceptionInterface;
use Allure\Behat\Printer\DummyOutputPrinter;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Gherkin\Node\ExampleNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Testwork\Counter\Timer;
use Behat\Testwork\EventDispatcher\Event\AfterSuiteTested;
use Behat\Testwork\EventDispatcher\Event\BeforeSuiteTested;
use Behat\Testwork\EventDispatcher\Event\SuiteTested;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Tester\Result\ExceptionResult;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Yandex\Allure\Adapter\Allure;
use Yandex\Allure\Adapter\AllureException;
use Yandex\Allure\Adapter\Annotation\AnnotationManager;
use Yandex\Allure\Adapter\Annotation\AnnotationProvider;
use Yandex\Allure\Adapter\Annotation\Description;
use Yandex\Allure\Adapter\Annotation\Issues;
use Yandex\Allure\Adapter\Annotation\Parameter;
use Yandex\Allure\Adapter\Annotation\Severity;
use Yandex\Allure\Adapter\Annotation\Stories;
use Yandex\Allure\Adapter\Annotation\TestCaseId;
use Yandex\Allure\Adapter\Event\StepCanceledEvent;
use Yandex\Allure\Adapter\Event\StepFailedEvent;
use Yandex\Allure\Adapter\Event\StepFinishedEvent;
use Yandex\Allure\Adapter\Event\StepStartedEvent;
use Yandex\Allure\Adapter\Event\TestCaseBrokenEvent;
use Yandex\Allure\Adapter\Event\TestCaseCanceledEvent;
use Yandex\Allure\Adapter\Event\TestCaseFailedEvent;
use Yandex\Allure\Adapter\Event\TestCaseFinishedEvent;
use Yandex\Allure\Adapter\Event\TestCasePendingEvent;
use Yandex\Allure\Adapter\Event\TestCaseStartedEvent;
use Yandex\Allure\Adapter\Event\TestSuiteFinishedEvent;
use Yandex\Allure\Adapter\Event\TestSuiteStartedEvent;
use Yandex\Allure\Adapter\Model\ConstantChecker;
use Yandex\Allure\Adapter\Model\DescriptionType;
use Yandex\Allure\Adapter\Model\Provider;
use Yandex\Allure\Adapter\Model\SeverityLevel;
use Yandex\Allure\Adapter\Support\AttachmentSupport;

class AllureFormatter implements Formatter
{
    protected $output;
    protected $name;
    protected $base_path;
    protected $timer;
    protected $exception;
    protected $attachment = [];
    protected $uuid;
    protected $issueTagPrefix;
    protected $testIdTagPrefix;
    protected $ignoredTags;
    protected $severity_key;
    protected $parameters;
    protected $printer;
    protected $outlineCounter = 0;

    /** @var \Behat\Testwork\Exception\ExceptionPresenter */
    protected $presenter;

    /** @var Allure */
    private $lifecycle;

    private $scopeAnnotation = [];

    use AttachmentSupport;

    public function __construct($name, $issue_tag_prefix, $test_id_tag_prefix, $ignoredTags, $severity_key, $base_path, $presenter)
    {
        $this->name = $name;
        $this->issueTagPrefix = $issue_tag_prefix;
        $this->testIdTagPrefix = $test_id_tag_prefix;
        $this->ignoredTags = $ignoredTags;
        $this->severity_key = $severity_key;
        $this->base_path = $base_path;
        $this->presenter = $presenter;
        $this->timer = new Timer();
        $this->printer = new DummyOutputPrinter();
        $this->parameters = new ParameterBag();
    }

    private function getLifeCycle()
    {
        if (!isset($this->lifecycle)) {
            $this->lifecycle = Allure::lifecycle();
        }

        return $this->lifecycle;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority),
     * array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
      SuiteTested::BEFORE => 'onBeforeSuiteTested',
      SuiteTested::AFTER => 'onAfterSuiteTested',
      ScenarioTested::BEFORE => 'onBeforeScenarioTested',
      ScenarioTested::AFTER => 'onAfterScenarioTested',
      StepTested::BEFORE => 'onBeforeStepTested',
      StepTested::AFTER => 'onAfterStepTested',
      ExampleTested::BEFORE => 'onBeforeScenarioTested',
      ExampleTested::AFTER => 'onAfterScenarioTested',
    ];
    }

    /**
     * Returns formatter name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns formatter description.
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Allure formatter for Behat 3';
    }

    /**
     * Returns formatter output printer.
     *
     * @return OutputPrinter
     */
    public function getOutputPrinter()
    {
        return $this->printer;
    }

    /**
     * Sets formatter parameter.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters->set($name, $value);
    }

    /**
     * Returns parameter name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        return $this->parameters->get($name);
    }

    public function onBeforeSuiteTested(BeforeSuiteTested $event)
    {
        AnnotationProvider::addIgnoredAnnotations([]);
        $this->prepareOutputDirectory(
      $this->printer->getOutputPath()
    );
        $start_event = new TestSuiteStartedEvent($event->getSuite()->getName());

        $this->uuid = $start_event->getUuid();

        $this->getLifeCycle()->fire($start_event);
    }

    public function onAfterSuiteTested(AfterSuiteTested $event)
    {
        AnnotationProvider::registerAnnotationNamespaces();
        $this->getLifeCycle()->fire(new TestSuiteFinishedEvent($this->uuid));
    }

    public function onBeforeScenarioTested(BeforeScenarioTested $event)
    {
        /** @var \Behat\Gherkin\Node\ScenarioNode $scenario */
        $scenario = $event->getScenario();
        /** @var \Behat\Gherkin\Node\FeatureNode $feature */
        $feature = $event->getFeature();

        $isExample = $scenario instanceof ExampleNode;

        $exampleAnnotations = ($isExample) ? $this->parseExampleAnnotations($scenario->getTokens()) : [];

        $annotations = array_merge(
      $this->parseFeatureAnnotations($feature),
      $this->parseScenarioAnnotations($scenario),
      $exampleAnnotations
    );

        $annotationManager = new AnnotationManager($annotations);
        $scenarioFilePath = str_replace($this->base_path . '/', '', $feature->getFile());
        $scenarioName = sprintf('%s:%d', $scenarioFilePath, $scenario->getLine());
        $scenarioEvent = new TestCaseStartedEvent($this->uuid, $scenarioName);
        $annotationManager->updateTestCaseEvent($scenarioEvent);

        $scenarioTitle = ($isExample) ? $scenario->getOutlineTitle() : $scenario->getTitle();

        $this->getLifeCycle()->fire($scenarioEvent->withTitle($scenarioTitle));
    }

    public function onAfterScenarioTested(AfterScenarioTested $event)
    {
        $this->processScenarioResult($event->getTestResult());
    }

    public function onBeforeStepTested(BeforeStepTested $event)
    {
        $step = $event->getStep();
        $stepEvent = new StepStartedEvent($step->getText());
        $stepEvent->withTitle(sprintf('%s %s', $step->getType(), $step->getText()));

        $this->getLifeCycle()->fire($stepEvent);
    }

    public function onAfterStepTested(AfterStepTested $event)
    {
        $result = $event->getTestResult();

        if ($result instanceof ExceptionResult && $result->hasException()) {
            $this->exception = $result->getException();
            if ($this->exception instanceof ArtifactExceptionInterface) {
                $this->attachment[md5_file($this->exception->getScreenPath())] = $this->exception->getScreenPath();
                $this->attachment[md5_file($this->exception->getHtmlPath())] = $this->exception->getHtmlPath();
            }
        }

        switch ($event->getTestResult()->getResultCode()) {
      case StepResult::FAILED:
        $this->addFailedStep();
        break;
      case StepResult::UNDEFINED:
        $this->addFailedStep();
        break;
      case StepResult::PENDING:
      case StepResult::SKIPPED:
        $this->addCancelledStep();
        break;
      case StepResult::PASSED:
      default:
        $this->exception = new \Exception('Error occurred out of test scope.');
    }
        $this->addFinishedStep();
    }

    protected function prepareOutputDirectory($outputDirectory)
    {
        if (!file_exists($outputDirectory)) {
            try {
                mkdir($outputDirectory, 0755, true);
            } catch (\ErrorException $ee) {
                if (!is_dir($outputDirectory)) {
                    throw $ee;
                }
            }
        }

        if (null === Provider::getOutputDirectory()) {
            Provider::setOutputDirectory($outputDirectory);
        }
    }

    protected function parseFeatureAnnotations(FeatureNode $featureNode)
    {
        $this->scopeAnnotation = $featureNode->getTags();
        $description = new Description();
        $description->type = DescriptionType::TEXT;
        $description->value = $featureNode->getDescription();

        return [$this->scopeAnnotation, $description];
    }

    protected function parseScenarioAnnotations(ScenarioInterface $scenarioNode)
    {
        $annotations = [];

        $story = new Stories();
        $story->stories = [];

        $issues = new Issues();
        $issues->issueKeys = [];

        $testId = new TestCaseId();
        $testId->testCaseIds = [];

        $severity = new Severity();

        $ignoredTags = [];

        $title = $scenarioNode instanceof ExampleNode ? $scenarioNode->getOutlineTitle() : $scenarioNode->getTitle();
        //$story->stories[] = $title;

        if (is_string($this->ignoredTags)) {
            $ignoredTags = array_map('trim', explode(',', $this->ignoredTags));
        } elseif (is_array($this->ignoredTags)) {
            $ignoredTags = $ignoredTags;
        }

        $annotation = array_merge($this->scopeAnnotation, $scenarioNode->getTags());
        foreach ($annotation as $tag) {
            if (in_array($tag, $ignoredTags)) {
                continue;
            }

            if ($this->issueTagPrefix) {
                if (stripos($tag, $this->issueTagPrefix) === 0) {
                    $issues->issueKeys[] = substr($tag, strlen($this->issueTagPrefix));
                    continue;
                }
            }

            if ($this->testIdTagPrefix) {
                if (stripos($tag, $this->testIdTagPrefix) === 0) {
                    $testId->testCaseIds[] = substr($tag, strlen($this->testIdTagPrefix));
                    continue;
                }
            }

            if ($this->severity_key && stripos($tag, $this->severity_key) === 0) {
                $level = preg_replace("/$this->severity_key/", '', $tag);
                try {
                    $level = ConstantChecker::validate('Yandex\Allure\Adapter\Model\SeverityLevel', $level);
                    $severity->level = $level;
                } catch (AllureException $e) {
                    $severity->level = SeverityLevel::NORMAL;
                }
                array_push($annotations, $severity);
                continue;
            }

            $story->stories[] = $tag;
        }

        if ($story->getStories()) {
            array_push($annotations, $story);
        }
        if ($issues->getIssueKeys()) {
            array_push($annotations, $issues);
        }
        if ($testId->getTestCaseIds()) {
            array_push($annotations, $testId);
        }

        return $annotations;
    }

    protected function processScenarioResult($result)
    {
        if ($result instanceof ExceptionResult && $result->hasException()) {
            $this->exception = $result->getException();
        }

        switch ($result->getResultCode()) {
      case StepResult::FAILED:
        $this->addTestCaseFailed();
        break;
      case StepResult::UNDEFINED:
        $this->addTestCaseBroken();
        break;
      case StepResult::PENDING:
        $this->addTestCasePending();
        break;
      case StepResult::SKIPPED:
        $this->addTestCaseCancelled();
        break;
      case StepResult::PASSED:
      default:
        $this->exception = new \Exception('Error occurred out of test scope.');
    }
        $this->addTestCaseFinished();
    }

    protected function parseExampleAnnotations(array $tokens)
    {
        $parameters = [];

        foreach ($tokens as $name => $value) {
            $parameter = new Parameter();
            $parameter->name = $name;
            $parameter->value = $value;
            $parameters[] = $parameter;
        }

        return $parameters;
    }

    protected function addAttachments()
    {
        array_walk($this->attachment, function ($path, $key) {
            $this->addAttachment($path, $key . '-attachment');
        });
    }

    private function addCancelledStep()
    {
        $event = new StepCanceledEvent();
        $this->getLifeCycle()->fire($event);
    }

    private function addFinishedStep()
    {
        $event = new StepFinishedEvent();
        $this->getLifeCycle()->fire($event);
    }

    private function addFailedStep()
    {
        $event = new StepFailedEvent();
        $this->getLifeCycle()->fire($event);
    }

    private function addTestCaseFinished()
    {
        $event = new TestCaseFinishedEvent();
        $this->getLifeCycle()->fire($event);
    }

    private function addTestCaseCancelled()
    {
        $event = new TestCaseCanceledEvent();
        $this->getLifeCycle()->fire($event);
    }

    private function addTestCasePending()
    {
        $event = new TestCasePendingEvent();
        $this->getLifeCycle()->fire($event);
    }

    private function addTestCaseBroken()
    {
        $event = new TestCaseBrokenEvent();
        $this->getLifeCycle()->fire($event);
    }

    private function addTestCaseFailed()
    {
        $event = new TestCaseFailedEvent();
        $event->withException($this->exception)
      ->withMessage($this->exception->getMessage());
        $this->addAttachments();

        $this->getLifeCycle()->fire($event);
    }
}
