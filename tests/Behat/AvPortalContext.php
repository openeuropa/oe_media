<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Behat context for AV Portal.
 */
class AvPortalContext extends RawDrupalContext {

  /**
   * Enables the Mock.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The scope.
   *
   * @beforeScenario @av_portal
   */
  public function enableTestModule(BeforeScenarioScope $scope): void {
    \Drupal::service('module_installer')->install(['media_avportal_mock', 'oe_media_avportal_test']);
  }

  /**
   * Disables the Mock.
   *
   * @param \Behat\Behat\Hook\Scope\AfterScenarioScope $scope
   *   The scope.
   *
   * @afterScenario @av_portal
   */
  public function disableTestModule(AfterScenarioScope $scope): void {
    \Drupal::service('module_installer')->uninstall(['media_avportal_mock', 'oe_media_avportal_test']);
  }

  /**
   * Fills in the Demo content AV Portal reference field for the video type.
   *
   * @param string $title
   *   The media title.
   *
   * @Given I reference the AV Portal video :title
   */
  public function assertReferenceAvPortalVideo(string $title): void {
    $this->getSession()->getPage()->fillField('field_oe_demo_av_portal_video[0][target_id]', $title);
  }

  /**
   * Fills in the Demo content AV Portal reference field for the photo type.
   *
   * @param string $title
   *   The media title.
   *
   * @Given I reference the AV Portal photo :title
   */
  public function assertReferenceAvPortalPhoto(string $title): void {
    $this->getSession()->getPage()->fillField('field_oe_demo_av_portal_photo[0][target_id]', $title);
  }

  /**
   * Checks that the AV Portal video is rendered.
   *
   * @param string $title
   *   The video title.
   *
   * @Then I should see the AV Portal video :title
   */
  public function assertAvPortalVideoIframe(string $title): void {
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['name' => $title]);
    if (!$media) {
      throw new \Exception(sprintf('The media named "%s" does not exist', $title));
    }

    $media = reset($media);
    $ref = $media->get('oe_media_avportal_video')->value;
    $this->assertSession()->elementAttributeContains('css', 'iframe', 'src', $ref);
  }

  /**
   * Checks that the AV Portal photo is rendered.
   *
   * @param string $title
   *   The photo title.
   * @param string $src
   *   The final photo source.
   *
   * @Then I should see the AV Portal photo :title with source :src
   */
  public function assertAvPortalPhoto(string $title, string $src): void {
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['name' => $title]);
    if (!$media) {
      throw new \Exception(sprintf('The media named "%s" does not exist', $title));
    }

    $this->assertSession()->elementAttributeContains('css', 'img.avportal-photo', 'src', $src);
  }

  /**
   * Find a item by title and click on checkbox.
   *
   * @param string $title
   *   Title of the item.
   *
   * @When I select the avportal item with the title :title
   */
  public function iSelectAvPortalItemByTitle(string $title): void {
    $xpath = "//div[@class and contains(concat(' ', normalize-space(@class), ' '), ' views-col ')]";
    $xpath .= "[.//div[@class and contains(concat(' ', normalize-space(@class), ' '), ' views-field-title ')][contains(string(.), '$title')]]";
    $xpath .= "//input[@type='checkbox']";
    $this->getSession()->getPage()->find('xpath', $xpath)->check();
  }

}
