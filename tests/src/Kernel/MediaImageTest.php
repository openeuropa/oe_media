<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\Kernel;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Kernel\MediaKernelTestBase;

/**
 * Tests image media.
 */
class MediaImageTest extends MediaKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_media',
    'file_link',
    'link',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['oe_media']);
  }

  /**
   * Tests that the image thumbnail alt is updated if the image alt is updated.
   */
  public function testImageAltSynchronisation(): void {
    $test_image_bundle = $this->createMediaType('image');
    $image_path = \Drupal::service('file_system')->copy(
      \Drupal::service('extension.list.module')->getPath('oe_media') . '/tests/fixtures/example_1.jpeg',
      'public://image.png'
    );

    $test_cases = [
      'image' => 'oe_media_image',
      $test_image_bundle->id() => $test_image_bundle->getSource()->getSourceFieldDefinition($test_image_bundle)->getName(),
    ];

    foreach ($test_cases as $bundle_id => $field_name) {
      // Create a new image file for each iteration.
      $file = File::create(['uri' => $image_path]);
      $file->setPermanent();
      $file->save();

      $media = Media::create([
        'bundle' => $bundle_id,
        'name' => 'Test image',
        $field_name => [
          [
            'target_id' => $file->id(),
            'alt' => 'default alt',
          ],
        ],
      ]);
      $media->save();

      // Now simulate a change in the alt text.
      $media->get($field_name)->alt = 'new alt';
      $media->save();

      // Assert the thumbnail alt has been updated.
      $this->assertEquals('new alt', $media->get('thumbnail')->alt, sprintf('Failed asserting alt text for bundle "%s".', $bundle_id));
    }
  }

}
