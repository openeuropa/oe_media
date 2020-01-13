<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_embed\FunctionalJavascript;

use Drupal\Core\Url;

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
    foreach (['Embed', 'Image teaser'] as $plugin) {
      $this->assertSession()->optionExists('Display as', $plugin);
    }
    $this->assertSession()->optionNotExists('Display as', 'Default');

    // Make the embed remote video view display not embeddable.
    $this->configureEmbeddableMediaViewMode('remote_video', 'Embed', 'disable');

    // Remote video without embeddable view modes.
    $this->getEmbedDialog('html', 'media');
    $title = 'Digital Single Market: cheaper calls to other EU countries as of 15 May (2)';
    $this->assertSession()->fieldExists('entity_id')->setValue($title);
    $this->assertSession()->buttonExists('Next')->press();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Assert that it is not possible to embed the media.
    $this->assertSession()->pageTextContainsOnce('Selected entity');
    $this->assertSession()->linkExists('Digital Single Market: cheaper calls to other EU countries as of 15 May');
    $this->assertSession()->pageTextContainsOnce('There is no embeddable view mode for this media type.');
    $this->assertSession()->buttonNotExists("Embed");

    // Revert the configuration change on the remote video view display.
    $this->configureEmbeddableMediaViewMode('remote_video', 'Embed');

    $this->getEmbedDialog('html', 'media');
    $title = 'Digital Single Market: cheaper calls to other EU countries as of 15 May (2)';
    $this->assertSession()->fieldExists('entity_id')->setValue($title);
    $this->assertSession()->buttonExists('Next')->press();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Assert that it is now possible to embed the media.
    $this->assertSession()->pageTextContainsOnce('Selected entity');
    $this->assertSession()->linkExists('Digital Single Market: cheaper calls to other EU countries as of 15 May');
    $this->assertSession()->pageTextNotContains('There is no embeddable view mode for this media type.');
    $this->assertSession()->buttonExists('Embed');
  }

  /**
   * Configures a view mode so it becomes available to be embedded.
   *
   * @param string $media_type
   *   The media type for which to enable/disable the view mode.
   * @param string $view_mode
   *   The view mode.
   * @param string $action
   *   Whether to enable or disable.
   */
  protected function configureEmbeddableMediaViewMode(string $media_type, string $view_mode, string $action = 'enable') {
    $this->drupalGet(Url::fromRoute('entity.entity_view_display.media.default', [
      'media_type' => $media_type,
    ]));
    // Open the Custom Display Settings details element.
    $this->click('#edit-modes summary');
    $embeddable_form_container = $this->getSession()->getPage()->find('css', '#edit-embeddable-displays');
    if ($action === 'disable') {
      $this->assertSession()->checkboxChecked('Embed', $embeddable_form_container);
      $embeddable_form_container->uncheckField($view_mode);
    }
    if ($action === 'enable') {
      $this->assertSession()->checkboxNotChecked('Embed', $embeddable_form_container);
      $embeddable_form_container->checkField($view_mode);
    }

    $this->assertSession()->buttonExists('Save')->press();
  }

}
