<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Behat context for AV Portal.
 */
class AvPortalContext extends RawDrupalContext {

  /**
   * Fills in the Demo content AV Portal reference field.
   *
   * @param string $title
   *   The media title.
   *
   * @Given I reference the AV Portal media :title
   */
  public function assertReferenceAvPortalMedia(string $title): void {
    $this->getSession()->getPage()->fillField('field_oe_demo_av_portal_video[0][target_id]', $title);
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

}
