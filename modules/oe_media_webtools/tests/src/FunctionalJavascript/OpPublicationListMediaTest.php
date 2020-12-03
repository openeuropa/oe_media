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
    'oe_webtools',
    'oe_webtools_media',
    'oe_media_webtools',
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

    $this->assertSession()->pageTextContains('Enter the widget id of the snippet generated in OP Website.');
    $this->getSession()->getPage()->fillField('Name', 'Publication list');
    $this->getSession()->getPage()->fillField('Webtools OP Publication lists snippet', 'Test media');
    $this->getSession()->getPage()->pressButton('Save');
    // Assert form element validation.
    $this->assertSession()->pageTextContains('The Webtools OP Publication lists snippet has to contain only digits.');

    $this->getSession()->getPage()->fillField('Webtools OP Publication lists snippet', '6313');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('Webtools op publication list Publication list has been created.');

    // Create a node and reference the created media.
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField('Title', 'Node with Publication list media');
    $publication_list_field = $this->getSession()->getPage()->find('css', 'div.field--name-field-oe-demo-webtools-op');
    $publication_list_field->fillField('Use existing media', 'Publication list');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('Webtools op publication list');
  }

}
