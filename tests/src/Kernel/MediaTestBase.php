<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for testing Media entities.
 */
abstract class MediaTestBase extends KernelTestBase {

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
    'file_link',
    'link',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'media',
      'image',
      'file',
      'system',
      'oe_media',
    ]);

    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installEntitySchema('media');
    $this->installSchema('file', ['file_usage']);
  }

}
