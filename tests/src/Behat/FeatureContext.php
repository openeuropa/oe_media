<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\Behat;

use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use PHPUnit\Framework\Assert;

/**
 * Provides step definitions to interact with page elements.
 */
class FeatureContext extends RawMinkContext {

  /**
   * Checks that a select field has exclusively the provided options.
   *
   * @param string $select
   *   The name of the select element.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The list of expected options.
   *
   * @Then the available options in the :select select should be:
   */
  public function assertSelectOptions(string $select, TableNode $table): void {
    $field = $this->getSession()->getPage()->find('named', ['select', $select]);

    if (empty($field)) {
      throw new \Exception(sprintf('Select field "%s" not found.', $select));
    }

    $available_options = [];
    foreach ($field->findAll('xpath', '//option') as $element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      $available_options[$element->getValue()] = trim($element->getText());
    }
    sort($available_options);

    $options = $table->getColumn(0);
    sort($options);

    Assert::assertEquals($options, $available_options, sprintf('The "%s" select options don\'t match the expected ones.', $select));
  }

}
