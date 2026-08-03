<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media_iframe\FunctionalJavascript;

use Drupal\Tests\media\FunctionalJavascript\MediaSourceTestBase;

/**
 * Tests the iframe source UI.
 */
class IframeSourceTest extends MediaSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
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
    // form display, with the expected widgets and ordering. The placement is
    // done by the source plugin's prepareFormDisplay(), so we assert the saved
    // form display configuration directly (loadUnchanged() avoids any stale
    // cache in the test runner process).
    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->loadUnchanged('media.iframe_source_test.default');
    $source_component = $form_display->getComponent('field_media_oe_media_iframe');
    $this->assertNotNull($source_component);
    $this->assertEquals('oe_media_iframe_textarea', $source_component['type']);
    $title_component = $form_display->getComponent('oe_media_iframe_title');
    $this->assertNotNull($title_component);
    $this->assertEquals('string_textfield', $title_component['type']);
    $thumbnail_component = $form_display->getComponent('oe_media_iframe_thumbnail');
    $this->assertNotNull($thumbnail_component);
    $this->assertEquals('image_image', $thumbnail_component['type']);
    // Iframe title field should be placed after the name field.
    $name_component = $form_display->getComponent('name');
    $this->assertEquals((int) $name_component['weight'] + 1, (int) $title_component['weight']);
    // The thumbnail field should be placed after the source field.
    $this->assertEquals((int) $source_component['weight'] + 1, (int) $thumbnail_component['weight']);

    // The correct formatter should be used in the view display.
    $view_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->loadUnchanged('media.iframe_source_test.default');
    $source_view_component = $view_display->getComponent('field_media_oe_media_iframe');
    $this->assertNotNull($source_view_component);
    $this->assertEquals('oe_media_iframe', $source_view_component['type']);
  }

}
