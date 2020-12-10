<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_webtools\FunctionalJavascript;

use Drupal\Tests\media\FunctionalJavascript\MediaSourceTestBase;

/**
 * Tests Webtools OP Publication list media type.
 */
class OpPublicationListMediaTest extends MediaSourceTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'json_field',
    'oe_media_demo',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test the webtools op publication list media type.
   */
  public function testOpPublicationMedia(): void {
    $this->drupalLogin($this->drupalCreateUser([], '', TRUE));
    $this->drupalGet('media/add/webtools_op_publication_list');

    $this->assertSession()->pageTextContains('Enter the widget id of the snippet generated on the OP Website.');
    $this->getSession()->getPage()->fillField('Name', 'Publication list');
    $this->getSession()->getPage()->fillField('Webtools OP Publication lists snippet', '6313');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('Webtools op publication list Publication list has been created.');

    // Assert value is massaged and stored properly.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $media_storage */
    $media_storage = \Drupal::service('entity_type.manager')->getStorage('media');
    $existing_media = $media_storage->loadMultiple();
    $this->assertCount(1, $existing_media);
    /** @var \Drupal\media\MediaInterface $media */
    $media = reset($existing_media);
    $stored_value = $media->get('oe_media_webtools')->first()->getString();
    $this->assertEquals('{ "service": "opwidget", "widgetId": "6313" }', $stored_value);

    // Assert the value is properly formatted when editing the media.
    $this->drupalGet($media->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('Webtools OP Publication lists snippet', '6313');

    // Create a node and reference the created media.
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField('Title', 'Node with Publication list media');
    $publication_list_field = $this->getSession()->getPage()->find('css', 'div.field--name-field-oe-demo-webtools-op');
    $publication_list_field->fillField('Use existing media', 'Publication list');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('Webtools op publication list');
  }

}
