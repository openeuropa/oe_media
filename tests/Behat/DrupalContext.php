<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * The main Drupal context.
 */
class DrupalContext extends RawDrupalContext {

  /**
   * Clicks on a fieldset form element.
   *
   * @param string $field
   *   The name of the fieldset.
   *
   * @Given I click the fieldset :field
   */
  public function assertClickFieldset(string $field): void {
    $this->getSession()->getPage()->find('named', ['link_or_button', $field])->click();
  }

  /**
   * Switches to the iframe of the Demo entity browser.
   *
   * @Then I should see entity browser modal window
   */
  public function iSwitchToIframe(): void {
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
  }

  /**
   * Selects required media entity from entity browser.
   *
   * @param string $name
   *   The name of the media.
   *
   * @When I select the :media_name media entity in the entity browser modal window
   */
  public function iSelectMediaInEntityBrowser(string $name): void {
    $xpath = "//div[@class and contains(concat(' ', normalize-space(@class), ' '), ' views-row ')]";
    $xpath .= "[.//div[@class and contains(concat(' ', normalize-space(@class), ' '), ' views-field-name ')][contains(string(.), '$name')]]";
    $xpath .= "//input[@type='checkbox']";
    $this->getSession()->getPage()->find('xpath', $xpath)->check();
  }

  /**
   * Step that deletes a media entity.
   *
   * @param string $title
   *   The media title.
   *
   * @Then I remove the media :title
   */
  public function removeMediaEntity(string $title): void {
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['name' => $title]);
    if (!$media) {
      throw new \Exception(sprintf('The media named "%s" does not exist', $title));
    }

    $media = reset($media);
    $media->delete();
  }

}
