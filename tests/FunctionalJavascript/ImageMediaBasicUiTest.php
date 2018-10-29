<?php

namespace Drupal\Tests\oe_media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the display UI of OE Media Image type.
 *
 * @group oe_media
 */
class ImageMediaBasicUiTest extends WebDriverTestBase {

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
      'create image media',
    ]);

    $this->drupalLogin($editor);
    exec('chmod -R 777 ' . $this->root . '/sites/simpletest');
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

    // Create a node with attached media.
    $this->drupalGet("node/add/oe_media_demo");
    $page->fillField("title[0][value]", 'My Node');
    $autocomplete_field = $page->findField('field_oe_demo_image_media[0][target_id]');
    $autocomplete_field->setValue('My Image 1');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();
    $this->getSession()->getDriver()->click($page->find('css', '.ui-autocomplete li')->getXpath());
    $page->pressButton('Save');
    $assert_session->addressEquals('/node/1');
    $assert_session->elementAttributeContains('css', '.field--name-oe-media-image>img', 'src', 'example_1.jpeg');
  }

}
