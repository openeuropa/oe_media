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
   * Checks that the Webtools map is rendered.
   *
   * @Then I should see the Webtools map
   */
  public function assertWebtoolsMap(): void {
    $this->assertSession()->elementExists('css', '.map.wt.wt-map');
  }

}
