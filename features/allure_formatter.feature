Feature: Allure Formatter
  In order integrate with Allure test report tool
  As a developer
  I need to be able to generate a allure-compatible report

  Scenario: Scenario annotation
    Given a file named "behat.yml" with:
      """
      default:
        formatters:
          pretty: false
          allure: true
        extensions:
          Allure\Behat\AllureFormatterExtension:
            severity_key: "severity:"
            ignored_tags: "tag_ignore"
            issue_tag_prefix: "JIRA:"
            test_id_tag_prefix: "BUG:"
      """
    Given a file named "features/bootstrap/FeatureContext.php" with:
      """
      <?php
      use Behat\Behat\Context\Context,
          Behat\Behat\Tester\Exception\PendingException;
      class FeatureContext implements Context
      {
          /**
           * @Given /^scenario has annotation$/
           */
          public function scenarioHasAnnotation() {
              return;
          }
          /**
           * @When /^it passed$/
           */
          public function iAdd() {
              return;
          }
          /**
           * @Then /^annotation is collected$/
           */
          public function somethingNotDoneYet() {
              return;
          }
      }
      """
    And a file named "features/World.feature" with:
      """
      @tag_feature @severity:blocker @JIRA:PROD-4444
      Feature: Annotation
        In order to have meta information of the scenario
        As a features developer
        I want, allure to collect all feature & scenarios tags

        @tag_scenario @BUG:7654 @tag_ignore
        Scenario: Scenario annotation
          Given scenario has annotation
          When it passed
          Then annotation is collected
      """
    When I run "behat --no-colors -f allure -o allure-results"
    Then "allure-results/*testsuite.xml" file xml should be like:
      """
      <?xml version="1.0" encoding="UTF-8"?>
      <alr:test-suite xmlns:alr="urn:model.allure.qatools.yandex.ru" start="-IGNORE-VALUE-" stop="-IGNORE-VALUE-" version="1.4.0">
        <name>default</name>
        <test-cases>
          <test-case start="-IGNORE-VALUE-" stop="-IGNORE-VALUE-" status="passed">
            <name>World.feature:8</name>
            <title>Scenario annotation</title>
            <description type="text"><![CDATA[In order to have meta information of the scenario
      As a features developer
      I want, allure to collect all feature & scenarios tags]]></description>
            <steps>
              <step start="-IGNORE-VALUE-" stop="-IGNORE-VALUE-" status="passed">
                <name>scenario has annotation</name>
                <title>Given scenario has annotation</title>
              </step>
              <step start="-IGNORE-VALUE-" stop="-IGNORE-VALUE-" status="passed">
                <name>it passed</name>
                <title>When it passed</title>
              </step>
              <step start="-IGNORE-VALUE-" stop="-IGNORE-VALUE-" status="passed">
                <name>annotation is collected</name>
                <title>Then annotation is collected</title>
              </step>
            </steps>
            <labels>
              <label name="severity" value="blocker"/>
              <label name="story" value="tag_feature"/>
              <label name="story" value="tag_scenario"/>
              <label name="issue" value="PROD-4444"/>
              <label name="testId" value="7654"/>
            </labels>
          </test-case>
        </test-cases>
      </alr:test-suite>
      """