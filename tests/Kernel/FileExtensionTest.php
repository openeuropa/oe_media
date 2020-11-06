<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Kernel;

use Drupal\field\Entity\FieldConfig;

/**
 * Tests that media types that allow file uploads use the correct extensions.
 */
class FileExtensionTest extends MediaTestBase {

  /**
   * Tests that the fields are correctly configured for file extensions.
   */
  public function testConfiguredFileExtensions(): void {
    $fields = [
      'media.document.oe_media_file' => 'txt text md readme info doc dot docx dotx docm dotm xls xlt xla xlsx xltx xlsm xltm xlam xlsb ppt pot pps ppa pptx potx ppsx ppam pptm potm ppsm pdf ods odt odf',
      'media.document.oe_media_remote_file' => 'txt text md readme info doc dot docx dotx docm dotm xls xlt xla xlsx xltx xlsm xltm xlam xlsb ppt pot pps ppa pptx potx ppsx ppam pptm potm ppsm pdf ods odt odf',
      'media.image.oe_media_image' => 'png gif jpg jpeg',
    ];

    foreach ($fields as $name => $extensions) {
      $config = FieldConfig::load($name);
      $this->assertEquals($extensions, $config->getSetting('file_extensions'));
    }
  }

}
