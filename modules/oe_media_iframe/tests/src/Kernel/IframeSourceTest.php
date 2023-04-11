<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_iframe\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\media\Entity\MediaType;
use Drupal\Tests\media\Kernel\MediaKernelTestBase;

/**
 * Tests iframe media source.
 */
class IframeSourceTest extends MediaKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'oe_media',
    'oe_media_iframe',
    'options',
    'file_link',
    'link',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'options',
      'oe_media',
      'oe_media_iframe',
    ]);
  }

  /**
   * Tests logic related to the automated custom fields creation.
   */
  public function testIframeSourceFieldsCreation() {
    /** @var \Drupal\media\MediaTypeInterface $type */
    $type = MediaType::create([
      'id' => 'test_iframe',
      'label' => 'Test iframe source',
      'source' => 'oe_media_iframe',
    ]);
    $type->save();

    /** @var \Drupal\field\Entity\FieldConfig $field */
    $type->getSource()->createSourceField($type);
    $field = FieldConfig::load('media.test_iframe.oe_media_iframe_thumbnail');
    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $field_storage = $field->getFieldStorageDefinition();

    // Assert that the thumbnail field storage is loaded correctly (or created
    // if it doesn't exist).
    $this->assertSame('image', $field_storage->getType());
    $this->assertSame('oe_media_iframe_thumbnail', $field_storage->getName());
    $this->assertSame('media', $field_storage->getTargetEntityTypeId());

    // Assert that the thumbnail field is created correctly.
    $this->assertSame('oe_media_iframe_thumbnail', $field->getName());
    $this->assertSame('image', $field->getType());
    $this->assertFalse($field->isRequired());
    $this->assertEquals('Iframe thumbnail', $field->label());
    $this->assertSame('test_iframe', $field->getTargetBundle());

    $field = FieldConfig::load('media.test_iframe.oe_media_iframe_title');
    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $field_storage = $field->getFieldStorageDefinition();

    // Assert that the field storage is loaded correctly (or created if it
    // doesn't exist).
    $this->assertSame('string', $field_storage->getType());
    $this->assertSame('oe_media_iframe_title', $field_storage->getName());
    $this->assertSame('media', $field_storage->getTargetEntityTypeId());

    // Assert that the field is created correctly.
    $this->assertSame('oe_media_iframe_title', $field->getName());
    $this->assertSame('string', $field->getType());
    $this->assertFalse($field->isRequired());
    $this->assertEquals('Iframe title', $field->label());
    $this->assertEquals('Providing an Iframe title value will replace the title value in the iframe html.', $field->getDescription());
    $this->assertSame('test_iframe', $field->getTargetBundle());
  }

}
