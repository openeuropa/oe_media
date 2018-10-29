<?php

namespace Drupal\Tests\oe_media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the display UI of OE Media Image type.
 *
 * @group oe_media
 */
class ImageMediaBasicUITest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'oe_media_demo',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $editor = $this->drupalCreateUser([
      'create oe_media_demo content',
      'create image media'
    ]);
    $this->drupalLogin($editor);

  }

  /**
   * Test the creation of Media image entity.
   */
  public function testCreateImageMedia() {

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Create a media item.
    $this->drupalGet("media/add/image");
    $page->attachFileToField("files[oe_media_image_0]", $this->root . '/modules/custom/oe_media/tests/fixtures/example_1.jpeg');
    $result = $assert_session->waitForButton('Remove');
    $this->createScreenshot('/var/www/html/build/screenshot.jpg');
    $this->assertNotEmpty($result);
    $page->fillField("oe_media_image_0[0][alt]", 'Image Alt Text 1');
    $page->pressButton('Save');

    $assert_session->addressEquals('admin/content/media');

    // Get the media entity view URL from the creation message.
    $this->drupalGet($this->assertLinkToCreatedMedia());

    // Make sure the thumbnail is displayed from uploaded image.
    $assert_session->elementAttributeContains('css', '.image-style-thumbnail', 'src', 'example_1.jpeg');

    // Load the media and check that all fields are properly populated.
    $media = Media::load(1);
    $this->assertSame('example_1.jpeg', $media->getName());
    $this->assertSame('200', $media->get('field_string_width')->value);
    $this->assertSame('89', $media->get('field_string_height')->value);
  }

  /**
   * Test the reusing of created Media image entity.
   */
//  public function testReuseCreatedImageMedia() {
//
//  }

}
