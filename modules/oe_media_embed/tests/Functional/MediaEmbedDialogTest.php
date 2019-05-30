<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_embed\Functional;

use Drupal\editor\Entity\Editor;

/**
 * Tests the media embed dialog.
 */
class MediaEmbedDialogTest extends MediaEmbedTestBase {

  /**
   * Tests access and configuration of the media embed dialog.
   */
  public function testMediaEmbedDialog(): void {
    // Ensure that the route is not accessible without specifying all the
    // parameters.
    $this->getEmbedDialog();
    $this->assertSession()->statusCodeEquals(404);
    $this->getEmbedDialog('html');
    $this->assertSession()->statusCodeEquals(404);

    // Ensure that the route is not accessible with an invalid embed button.
    $this->getEmbedDialog('html', 'invalid_button');
    $this->assertSession()->statusCodeEquals(404);

    // Ensure that the route is not accessible with text format without the
    // button configured.
    $this->getEmbedDialog('plain_text', 'media');
    $this->assertSession()->statusCodeEquals(404);

    // Add an empty configuration for the plain_text editor configuration.
    $editor = Editor::create([
      'format' => 'plain_text',
      'editor' => 'ckeditor',
    ]);
    $editor->save();
    $this->getEmbedDialog('plain_text', 'media');
    $this->assertSession()->statusCodeEquals(403);

    // Ensure that the route is accessible with a valid embed button.
    // 'Node' embed button is provided by default by the module and hence the
    // request must be successful.
    $this->getEmbedDialog('html', 'media');
    $this->assertSession()->statusCodeEquals(200);

    // Ensure form structure of the 'select' step and submit form.
    $this->assertFieldByXPath($this->constructFieldXpath('name', 'entity_id'), '');
    $this->assertFieldByXPath('//input[contains(@class, "button--primary")]', 'Next');
  }

}
