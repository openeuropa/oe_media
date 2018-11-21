<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\Core\Site\Settings;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * The main Drupal context.
 */
class DrupalContext extends RawDrupalContext {

  /**
   * Setup demo site.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The Hook scope.
   *
   * @BeforeScenario @demo
   */
  public function setupDemo(BeforeScenarioScope $scope): void {
    $this->enableTestModuleScanning();
    \Drupal::service('module_installer')->install(['oe_media_demo', 'media_avportal_mock']);
  }

  /**
   * Revert demo site setup.
   *
   * @param \Behat\Behat\Hook\Scope\AfterScenarioScope $scope
   *   The Hook scope.
   *
   * @AfterScenario @demo
   */
  public function revertDemoSetup(AfterScenarioScope $scope): void {
    $this->enableTestModuleScanning();
    \Drupal::service('module_installer')->uninstall([
      'oe_media_demo',
      'oe_media_avportal',
      'media_avportal',
      'media_avportal_mock',
    ]);

    $configs = [
      'core.entity_form_display.node.oe_media_demo.default',
      'core.entity_view_display.node.oe_media_demo.default',
      'entity_browser.browser.media_entity_browser',
      'field.field.media.av_portal_video.oe_media_avportal_video',
      'field.field.node.oe_media_demo.field_oe_demo_av_portal_video',
      'field.field.node.oe_media_demo.field_oe_demo_document_media',
      'field.field.node.oe_media_demo.field_oe_demo_image_media',
      'field.field.node.oe_media_demo.field_oe_demo_media_browser',
      'field.field.node.oe_media_demo.field_oe_demo_remote_video_media',
      'field.storage.media.oe_media_avportal_video',
      'field.storage.node.field_oe_demo_av_portal_video',
      'field.storage.node.field_oe_demo_document_media',
      'field.storage.node.field_oe_demo_image_media',
      'field.storage.node.field_oe_demo_media_browser',
      'field.storage.node.field_oe_demo_remote_video_media',
      'media.type.av_portal_video',
      'node.type.oe_media_demo',
      'views.view.media_entity_browser',
    ];

    foreach ($configs as $config) {
      \Drupal::configFactory()->getEditable($config)->delete();
    }

  }

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

  /**
   * Enables the test module scanning.
   *
   * The AV Portal media mock is a test module so it cannot be enabled by
   * default as it is not being scanned. By changing the settings temporarily,
   * we can allow that to happen.
   */
  protected function enableTestModuleScanning(): void {
    $settings = Settings::getAll();
    $settings['extension_discovery_scan_tests'] = TRUE;
    // We just have to re-instantiate the singleton.
    new Settings($settings);
  }

}
