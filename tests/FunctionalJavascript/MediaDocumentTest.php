<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests that we can create and use Document media entities.
 *
 * @group oe_media
 */
class MediaDocumentTest extends WebDriverTestBase {

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
      'create document media',
    ]);

    $this->drupalLogin($editor);
  }

  /**
   * Test the creation of Media document entity and reference on the Demo node.
   */
  public function testCreateDocumentMedia(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Create a media item.
    $this->drupalGet('media/add/document');
    $page->fillField('name[0][value]', 'My Document 1');
    $path = drupal_get_path('module', 'oe_media');
    $page->attachFileToField('files[oe_media_file_0]', $this->root . '/' . $path . '/tests/fixtures/sample.pdf');
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');
    $assert_session->addressEquals('/media/1');

    // Create a node with attached media.
    $this->drupalGet('node/add/oe_media_demo');
    $page->fillField('title[0][value]', 'My Node');
    $autocomplete_field = $page->findField('field_oe_demo_document_media[0][target_id]');
    $autocomplete_field->setValue('My Document 1');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();
    $this->getSession()->getDriver()->click($page->find('css', '.ui-autocomplete li')->getXpath());
    $page->pressButton('Save');
    $assert_session->addressEquals('/node/1');
    $assert_session->elementAttributeContains('css', '.field--name-oe-media-file>span>a', 'href', 'sample.pdf');
  }

}
