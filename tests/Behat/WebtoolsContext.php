<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Behat context for Webtools.
 */
class WebtoolsContext extends RawDrupalContext {

  /**
   * Fills in the Demo content Webtools map reference field for webtools map.
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
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['name' => $title]);
    if (!$media) {
      throw new \Exception(sprintf('The media named "%s" does not exist', $title));
    }
    $media = reset($media);
    $ref = $media->get('field_media_webtools')->value;
    $this->assertSession()->elementContains('css', '.field--name-field-media-webtools', '<script type="application/json">' . $ref . '</script>');
  }

}
