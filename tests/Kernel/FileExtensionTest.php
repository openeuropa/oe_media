<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Kernel;

use Drupal\field\Entity\FieldConfig;

/**
 * Tests that media types that allow file uploads use the correct extensions.
 */
class FileExtensionTest extends MediaTestBase {

  /**
   * Tests a document upload restriction.
   */
  public function testDocumentUploadFileExtensions(): void {
    $field = FieldConfig::load('media.document.oe_media_file');
    $this->assertEquals('txt text md readme info doc dot docx dotx docm dotm xls xlt xla xlsx xltx xlsm xltm xlam xlsb ppt pot pps ppa pptx potx ppsx ppam pptm potm ppsm pdf ods odt odf', $field->getSetting('file_extensions'));
  }

  /**
   * Tests document remote file restriction.
   */
  public function testDocumentRemoteFileExtensions(): void {
    $field = FieldConfig::load('media.document.oe_media_remote_file');
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
