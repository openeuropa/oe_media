<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests that we can create and use Image media entities.
 *
 * @group oe_media
 */
class MediaImageTest extends WebDriverTestBase {

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
      'access media_entity_browser entity browser pages',
    ]);

    // This first drupalGet() is needed.
    $this->drupalGet('<front>');

    $this->drupalLogin($editor);
  }

  /**
   * Test the creation of Media image entity and reference on the Demo node.
   */
  public function testCreateImageMedia(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Create a media item.
    $this->drupalGet('media/add/image');
    $page->fillField('name[0][value]', 'My Image 1');
    $path = drupal_get_path('module', 'oe_media');
    $page->attachFileToField('files[oe_media_image_0]', $this->root . '/' . $path . '/tests/fixtures/example_1.jpeg');
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->fillField('oe_media_image[0][alt]', 'Image Alt Text 1');
    $page->pressButton('Save');
    $assert_session->addressEquals('/media/1');

    // Create a node with attached media.
    $this->drupalGet('node/add/oe_media_demo');
    $page->fillField('title[0][value]', 'My Node');
    $autocomplete_field = $page->findField('field_oe_demo_image_media[0][target_id]');
    $autocomplete_field->setValue('My Image 1');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();
    $this->getSession()->getDriver()->click($page->find('css', '.ui-autocomplete li')->getXpath());
    $page->pressButton('Save');
    $assert_session->addressEquals('/node/1');
    $assert_session->elementAttributeContains('css', '.field--name-oe-media-image>img', 'src', 'example_1.jpeg');
  }

  /**
   * Test the creation of Media Image via IEF and reuse on the Demo node.
   */
  public function testAddImageViaEntityBrowser(): void {
    $image_filename = 'example_1.jpeg';
    // Add image.
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField('title[0][value]', $this->randomString());
    $this->click('#edit-field-oe-demo-media-browser-wrapper');
    $this->getSession()->getPage()->pressButton('Select entities');

    // Go to modal window.
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $iframe_page = $this->getSession()->getPage();
    $iframe_page->clickLink('Add Image');
    $this->getSession()->getPage()->fillField('inline_entity_form[name][0][value]', $this->randomString());
    $path = drupal_get_path('module', 'oe_media');
    $this->getSession()->getPage()->attachFileToField('files[inline_entity_form_oe_media_image_0]', $this->root . '/' . $path . '/tests/fixtures/' . $image_filename);
    $result = $this->assertSession()->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $this->getSession()->getPage()->fillField('inline_entity_form[oe_media_image][0][alt]', $this->randomString());
    $iframe_page->pressButton('Save entity');

    // Go back to main window.
    $this->getSession()->switchToIFrame();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->waitForButton('Remove');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->elementAttributeContains('css', '.field--name-oe-media-image>img', 'src', $image_filename);

    // Reuse previously added image.
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField('title[0][value]', $this->randomString());
    $this->click('#edit-field-oe-demo-media-browser-wrapper');
    $this->getSession()->getPage()->pressButton('Select entities');

    // Go to modal window with library of medias.
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $iframe_page = $this->getSession()->getPage();
    $iframe_page->clickLink('View');
    $iframe_page->findField('edit-entity-browser-select-media1')->click();
    $iframe_page->pressButton('Select entities');

    // Go back to main window and save node.
    $this->getSession()->switchToIFrame();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->waitForButton('Remove');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->elementAttributeContains('css', '.field--name-oe-media-image>img', 'src', $image_filename);
  }

}
