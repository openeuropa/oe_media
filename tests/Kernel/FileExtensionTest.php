<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that media types that allow file uploads use the correct extensions.
 */
class FileExtensionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'media',
    'user',
    'image',
    'file',
    'system',
    'oe_media',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig([
      'media',
      'image',
      'file',
      'system',
      'oe_media',
    ]);

  }

  /**
   * Tests a document upload restriction.
   */
  public function testDocumentUploadFileExtensions(): void {
    $field = FieldConfig::load('media.document.oe_media_file');
    $this->assertEquals('txt text md readme info doc dot docx dotx docm dotm xls xlt xla xlsx xltx xlsm xltm xlam xlsb ppt pot pps ppa pptx potx ppsx ppam pptm potm ppsm pdf ods odt odf', $field->getSetting('file_extensions'));
  }

  /**
   * Tests a image upload restriction.
   */
  public function testImageUploadFileExtensions(): void {
    $field = FieldConfig::load('media.image.oe_media_image');
    $this->assertEquals('png gif jpg jpeg', $field->getSetting('file_extensions'));
  }

}
