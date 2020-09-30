<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_iframe\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\Tests\oe_media\Traits\MediaTypeCreationTrait;

/**
 * Test the Media Iframe text formats.
 *
 * @group oe_media_iframe
 */
class MediaIframeFormatTest extends MediaFunctionalTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_media_iframe',
  ];

  /**
   * The media type.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $mediaType;

  /**
   * The source field.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $sourceField;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    \Drupal::configFactory()
      ->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save(TRUE);
    $this->container->get('router.builder')->rebuild();

    $media_type = $this->createMediaType('oe_media_iframe', [
      'id' => 'test_iframe',
      'label' => 'Test iframe source',
      'source' => 'oe_media_iframe',
    ]);

    $source = $media_type->getSource();
    $this->sourceField = $source->getConfiguration()['source_field'];
    $this->mediaType = $media_type;

    // Create user with permission for using oe_media_iframe text format.
    $this->adminUser = $this->createUser(array_merge(
      static::$adminUserPermissions,
      [FilterFormat::load('oe_media_iframe')->getPermissionName()]
    ));
  }

  /**
   * Tests that the widget configuration is applied to new iframe media sources.
   */
  public function testMediaSourceWidget(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/media/manage/' . $this->mediaType->id() . '/form-display');
    $this->assertTrue($this->assertSession()->optionExists('fields[' . $this->sourceField . '][region]', 'content')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists('fields[' . $this->sourceField . '][type]', 'oe_media_iframe_textarea')->isSelected());
    $this->assertTrue($this->assertSession()->optionExists('fields[' . $this->sourceField . '][type]', 'oe_media_iframe_textarea')->isSelected());

    $this->drupalPostForm(NULL, [], $this->sourceField . '_settings_edit');
    $this->assertText('Widget settings: Media iframe text area');
    $this->assertSession()->pageTextContains('Rows');
    $this->assertSession()->pageTextContains('Placeholder');

    $this->drupalGet('media/add/' . $this->mediaType->id());
    $this->assertSession()->fieldExists('Iframe');
    $this->assertSession()->pageTextContains('Allowed HTML tags: <iframe allowfullscreen height importance loading referrerpolicy sandbox src width mozallowfullscreen webkitAllowFullScreen scrolling frameborder>');
  }

}
