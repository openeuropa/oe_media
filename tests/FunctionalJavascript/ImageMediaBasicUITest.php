<?php

namespace Drupal\Tests\oe_media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;

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
   * Test the creation of Media image entity and reference on the Demo node.
   */
  public function testCreateImageMedia() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Create a media item.
    $this->drupalGet("media/add/image");
    $page->fillField("name[0][value]", 'My Image 1');
    $path = drupal_get_path('module', 'oe_media');
    $page->attachFileToField("files[oe_media_image_0]", $path . '/tests/fixtures/example_1.jpeg');
    $result = $assert_session->waitForButton('Remove');

    $this->assertNotEmpty($result);
    $page->fillField("oe_media_image[0][alt]", 'Image Alt Text 1');

    $page->pressButton('Save');
    $assert_session->addressEquals('/media/1');



  }

}
