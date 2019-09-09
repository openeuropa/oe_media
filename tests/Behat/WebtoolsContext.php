<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Behat;

use Drupal\Component\Serialization\Json;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Behat context for Webtools.
 */
class WebtoolsContext extends RawDrupalContext {

  /**
   * Fills in the Demo content Webtools chart reference field.
   *
   * Fills the field with a reference to a webtools chart media.
   *
   * @param string $title
   *   The webtools chart title.
   *
   * @Given I reference the Webtools chart :title
   */
  public function assertReferenceWebtoolsChart(string $title): void {
    $this->getSession()->getPage()->fillField('field_oe_demo_webtools_chart[0][target_id]', $title);
  }

  /**
   * Fills in the Demo content Webtools map reference field.
   *
   * Fills the field with a reference to a webtools map media.
   *
   * @param string $title
   *   The webtools map title.
   *
   * @Given I reference the Webtools map :title
   */
  public function assertReferenceWebtoolsMap(string $title): void {
    $this->getSession()->getPage()->fillField('field_oe_demo_webtools_map[0][target_id]', $title);
  }

  /**
   * Fills in the Demo content Webtools social feed reference field.
   *
   * Fills the field with a reference to a webtools social feed media.
   *
   * @param string $title
   *   The webtools social feed title.
   *
   * @Given I reference the Webtools social feed :title
   */
  public function assertReferenceWebtoolsSocialFeed(string $title): void {
    $this->getSession()->getPage()->fillField('field_oe_demo_webtools_sfeed[0][target_id]', $title);
  }

  /**
   * Checks that the Webtools widget is exist on the page.
   *
   * @param string $widget_type
   *   The webtools widget type.
   * @param string $title
   *   The webtools media title.
   *
   * @Then /^I should see the Webtools (map|chart|social feed) "([^"]*)" on the page$/
   */
  public function assertWebtoolsWidgetExist(string $widget_type, string $title): void {
    $bundles = [
      'map' => 'webtools_map',
      'chart' => 'webtools_chart',
      'social feed' => 'webtools_social_feed',
    ];

    $media = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties([
      'name' => $title,
      'bundle' => $bundles[$widget_type],
    ]);
    if (!$media) {
      throw new \Exception(sprintf('The %s media named "%s" does not exist', $widget_type, $title));
    }

    $media = reset($media);
    // Run the escaping on the Json data.
    $snippet = Json::encode(Json::decode($media->get('oe_media_webtools')->value));
    // Escape \ and ' for the xpath expression.
    $xpath = "//script[@type='application/json'][.='" . addcslashes($snippet, '\\\'') . "']";
    $this->assertSession()->elementsCount('xpath', $xpath, 1);
  }

}
