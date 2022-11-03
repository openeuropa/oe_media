<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Kernel;

use Drupal\media\Entity\Media;
use Drupal\Tests\media\Kernel\MediaKernelTestBase;

/**
 * Tests image media.
 */
class MediaImageTest extends MediaKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_media',
    'file_link',
    'link',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['oe_media']);
  }

  /**
   * Tests that the image thumbnail alt is updated if the image alt is updated.
   */
  public function testImageThumbnailAlt(): void {
    // Create image file.
    $file = file_save_data(file_get_contents(drupal_get_path('module', 'oe_media') . '/tests/fixtures/example_1.jpeg'), 'public://example_1.jpeg');
    $file->setPermanent();
    $file->save();

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Test image',
      'oe_media_image' => [
        [
          'target_id' => $file->id(),
          'alt' => 'default alt',
        ],
      ],
    ]);
    $media->save();

    // Now emulate a change in the alt text.
    $media->get('oe_media_image')->alt = 'new alt';
    $media->save();

    // Assert the thumbnail alt has been updated.
    $this->assertEquals('new alt', $media->get('thumbnail')->alt);
  }

}
