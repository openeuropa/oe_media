<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the media creation form entity browser widget.
 */
class MediaCreationFormWidgetTest extends WebDriverTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'system',
    'oe_media',
    'oe_media_demo',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([], '', TRUE));
  }

  /**
   * Tests the media creation form entity browser widget.
   */
  public function testMediaCreationForm(): void {
    $this->drupalGet('node/add/oe_media_demo');

    $this->getSession()->getPage()->pressButton('Media browser field');
    $media_browser_field = $this->getSession()->getPage()->find('css', 'div.field--name-field-oe-demo-media-browser');
    $media_browser_field->pressButton('Select entities');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
    $this->getSession()->getPage()->clickLink('Media creation form');
    // Assert that the bundle select field exists and contains only the allowed
    // target bundles.
    $this->assertSession()->selectExists('Bundle');
    $select_field = $this->getSession()->getPage()->findField('Bundle');
    $this->assertFieldSelectOptions($select_field, [
      '- Select -',
      'AV Portal Photo',
      'AV Portal Video',
      'Document',
      'Image',
      'Remote video',
    ]);
    // Assert that the bundle field is required.
    $this->assertSession()->elementAttributeContains('css', 'select#edit-media-bundle', 'required', 'required');
    $this->assertSession()->buttonExists('Save media');

    // Assert that only the allowed target bundles on a different field.
    $this->getSession()->reload();
    $image_field = $this->getSession()->getPage()->find('css', 'div.field--name-field-oe-demo-images-browser');
    $image_field->pressButton('Select entities');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
    $this->getSession()->getPage()->clickLink('Media creation form');
    $select_field = $this->getSession()->getPage()->findField('Bundle');
    $this->assertFieldSelectOptions($select_field, [
      '- Select -',
      'AV Portal Photo',
      'Image',
    ]);
    $this->getSession()->getPage()->selectFieldOption('Bundle', 'Image');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->fillField('Name', 'Test image');

    // Create a file for image media.
    $file = current($this->getTestFiles('image'));
    $image_file_path = \Drupal::service('file_system')->realpath($file->uri);
    $this->getSession()->getPage()->attachFileToField('Image', $image_file_path);
    $this->assertSession()->waitForField('Alternative text');
    $this->getSession()->getPage()->fillField('Alternative text', 'img alt');
    $this->getSession()->getPage()->pressButton('Save media');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Test image');
    $this->assertSession()->buttonExists('Remove');
    $this->assertSession()->buttonExists('Edit');
  }

  /**
   * Checks if a select element contains the specified options.
   *
   * @param \Behat\Mink\Element\NodeElement $field
   *   The select field to validate.
   * @param array $expected_options
   *   An array of expected options.
   */
  protected function assertFieldSelectOptions(NodeElement $field, array $expected_options): void {
    /** @var \Behat\Mink\Element\NodeElement[] $select_options */
    $select_options = $field->findAll('xpath', 'option');

    // Validate the number of options.
    $this->assertCount(count($expected_options), $select_options);

    // Validate the options and expected order.
    foreach ($select_options as $key => $option) {
      $this->assertEquals($option->getText(), $expected_options[$key]);
    }
  }

}
