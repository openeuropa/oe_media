<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_iframe\FunctionalJavascript;

use Drupal\Tests\media\FunctionalJavascript\MediaSourceTestBase;

/**
 * Tests the iframe source UI.
 */
class IframeSourceTest extends MediaSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_media_iframe',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the media type creation using the iframe source.
   */
  public function testMediaTypeCreation(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/media/add');

    // Create a media type through the UI, using the iframe media source.
    $this->getSession()->getPage()->fillField('Name', 'Iframe source test');
    $assert_session = $this->assertSession();
    $assert_session->waitForText('Machine name: iframe_source_test');
    $assert_session->selectExists('Media source')->selectOption('Iframe');
    $result = $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]');
    $this->assertNotEmpty($result);
    $this->assertTrue($assert_session->optionExists('Text format', 'oe_media_iframe', $result)->isSelected());
    $this->getSession()->getPage()->findButton('Save')->press();
    $assert_session->pageTextContains('The media type Iframe source test has been added.');

    // Verify that the source field and the thumbnail field are placed in the
    // form.
    $this->drupalGet('/admin/structure/media/manage/iframe_source_test/form-display');
    $this->assertEquals('content', $assert_session->fieldExists('fields[field_media_oe_media_iframe][region]')->getValue());
    $this->assertEquals('string_textfield', $assert_session->fieldExists('fields[oe_media_iframe_title][type]')->getValue());
    $this->assertEquals('oe_media_iframe_textarea', $assert_session->fieldExists('fields[field_media_oe_media_iframe][type]')->getValue());
    $this->assertEquals('content', $assert_session->fieldExists('fields[oe_media_iframe_thumbnail][region]')->getValue());
    $this->assertEquals('image_image', $assert_session->fieldExists('fields[oe_media_iframe_thumbnail][type]')->getValue());
    // Iframe title field should be placed after the name field.
    $name_field_weight = $assert_session->fieldExists('fields[name][weight]')->getValue();
    $iframe_title_field_weight = $assert_session->fieldExists('fields[oe_media_iframe_title][weight]')->getValue();
    $this->assertEquals((int) $iframe_title_field_weight, (int) $name_field_weight + 1);
    // The thumbnail field should be placed after the source field.
    $source_field_weight = $assert_session->fieldExists('fields[field_media_oe_media_iframe][weight]')->getValue();
    $thumbnail_field_weight = $assert_session->fieldExists('fields[oe_media_iframe_thumbnail][weight]')->getValue();
    $this->assertEquals((int) $thumbnail_field_weight, (int) $source_field_weight + 1);

    // The correct formatter should be used in the view display.
    $this->drupalGet('/admin/structure/media/manage/iframe_source_test/display');
    $this->assertEquals('content', $assert_session->fieldExists('fields[field_media_oe_media_iframe][region]')->getValue());
    $this->assertEquals('oe_media_iframe', $assert_session->fieldExists('fields[field_media_oe_media_iframe][type]')->getValue());
  }

}
