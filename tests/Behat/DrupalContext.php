<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Class DrupalContext.
 */
class DrupalContext extends RawDrupalContext {

  /**
   * Click on drupal fiedlset form element.
   *
   * @Given I click fieldset :field of entity browser widget
   */
  public function iClickFieldset(string $field): void {
    $this->getSession()->getPage()->find('named', ['link_or_button', $field])->click();
  }

  /**
   * Switching to the iframe.
   *
   * @Then I should see entity browser modal window
   */
  public function iSwitchToIframe(): void {
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
  }

  /**
   * Select required media from galary.
   *
   * @When I select the :media_name media entity in the entity browser modal window
   */
  public function iSelectMediaInEntityBrowser(string $media_name): void {
    $xpath = "//div[@class and contains(concat(' ', normalize-space(@class), ' '), ' views-row ')]";
    $xpath .= "[.//div[@class and contains(concat(' ', normalize-space(@class), ' '), ' views-field-name ')][contains(string(.), '$media_name')]]";
    $xpath .= "//input[@type='checkbox']";
    $this->getSession()->getPage()->find('xpath', $xpath)->check();
  }

}
