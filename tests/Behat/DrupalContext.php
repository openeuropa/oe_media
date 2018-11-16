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
   * @Given I click fieldset :field
   */
  public function iClickFieldset(string $field): void {
    $this->getSession()->getPage()->find('named', ['link_or_button', $field])->click();
  }

  /**
   * Switching to the iframe.
   *
   * @Given I switch to the :iframe iframe
   */
  public function iSwitchToIframe(string $iframe): void {
    $this->getSession()->switchToIFrame($iframe);
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
