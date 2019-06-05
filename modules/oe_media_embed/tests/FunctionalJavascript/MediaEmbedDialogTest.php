<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_embed\FunctionalJavascript;

/**
 * Tests the media embed dialog.
 */
class MediaEmbedDialogTest extends MediaEmbedTestBase {

  /**
   * Tests the media embed button markup.
   */
  public function testEntityEmbedButtonMarkup(): void {
    $this->getEmbedDialog('html', 'media');

    // Image media with view modes.
    $title = 'My image media (1)';
    $this->assertSession()->fieldExists('entity_id')->setValue($title);
    $this->assertSession()->buttonExists('Next')->press();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContainsOnce('Selected entity');
    $this->assertSession()->linkExists('My image media');
    foreach (['Default', 'Image teaser'] as $plugin) {
      $this->assertSession()->optionExists('Display as', $plugin);
    }

    // Remote video without view modes.
    $this->getEmbedDialog('html', 'media');
    $title = 'Digital Single Market: cheaper calls to other EU countries as of 15 May (2)';
    $this->assertSession()->fieldExists('entity_id')->setValue($title);
    $this->assertSession()->buttonExists('Next')->press();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContainsOnce('Selected entity');
    $this->assertSession()->linkExists('Digital Single Market: cheaper calls to other EU countries as of 15 May');
    $this->assertSession()->fieldNotExists('Display as');
  }

}
