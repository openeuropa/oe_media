<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_embed\FunctionalJavascript;

/**
 * Tests the media embed dialog.
 */
class MediaEmbedDialogTest extends MediaEmbedTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Make the default image view display embedable.
    $view_display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('media.image.default');
    $view_display->setThirdPartySetting('oe_media_embed', 'embedable', TRUE);
    $view_display->save();
  }

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
    $this->assertSession()->optionNotExists('Display as', 'Image not embedable');

    // Remote video without embedable view modes.
    $this->getEmbedDialog('html', 'media');
    $title = 'Digital Single Market: cheaper calls to other EU countries as of 15 May (2)';
    $this->assertSession()->fieldExists('entity_id')->setValue($title);
    $this->assertSession()->buttonExists('Next')->press();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Assert that it is not possible to embed the media.
    $this->assertSession()->pageTextContainsOnce('Selected entity');
    $this->assertSession()->linkExists('Digital Single Market: cheaper calls to other EU countries as of 15 May');
    $this->assertSession()->pageTextContainsOnce('There is no embedable view mode for this media type.');
    $this->assertSession()->buttonNotExists("Embed");

    // Make the default remove video view display embedable.
    $view_display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('media.remote_video.default');
    $view_display->setThirdPartySetting('oe_media_embed', 'embedable', TRUE);
    $view_display->save();

    $this->getEmbedDialog('html', 'media');
    $title = 'Digital Single Market: cheaper calls to other EU countries as of 15 May (2)';
    $this->assertSession()->fieldExists('entity_id')->setValue($title);
    $this->assertSession()->buttonExists('Next')->press();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Assert that it is not possible to embed the media.
    $this->assertSession()->pageTextContainsOnce('Selected entity');
    $this->assertSession()->linkExists('Digital Single Market: cheaper calls to other EU countries as of 15 May');
    $this->assertSession()->pageTextNotContains('There is no embedable view mode for this media type.');
    $this->assertSession()->buttonExists("Embed");
  }

}
