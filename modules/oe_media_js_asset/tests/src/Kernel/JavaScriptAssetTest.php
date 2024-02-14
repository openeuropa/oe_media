<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media_js_asset\Kernel;

use Drupal\Tests\media\Kernel\MediaKernelTestBase;

/**
 * Tests JavaScript asset media.
 */
class JavaScriptAssetTest extends MediaKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'oe_media',
    'oe_media_js_asset',
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
      'oe_media_js_asset',
    ]);
  }

  /**
   * Tests JS asset media item.
   */
  public function testJavaScriptAssetMediaItem() {
    $media_storage = \Drupal::entityTypeManager()->getStorage('media');
    $entity = $media_storage->create([
      'bundle' => 'javascript_asset',
      'name' => 'First JavaScript asset',
      'oe_media_js_asset_url' => [
        'environment' => 'acceptance',
        'path' => '/somejavascript.js',
      ],
      'status' => TRUE,
    ]);
    $entity->save();

    $entity = $media_storage->load($entity->id());
    $this->assertEquals('First JavaScript asset', $entity->label());
    $this->assertEquals('acceptance', $entity->get('oe_media_js_asset_url')->environment);
    $this->assertEquals('/somejavascript.js', $entity->get('oe_media_js_asset_url')->path);
    $this->assertFalse($entity->get('oe_media_js_asset_url')->isEmpty());

    // Test different cases when the field is considered as empty.
    $entity->set('oe_media_js_asset_url', [
      'environment' => '',
      'path' => '/somejavascript.js',
    ])->save();
    $this->assertTrue($entity->get('oe_media_js_asset_url')->isEmpty());
    $entity->set('oe_media_js_asset_url', [
      'environment' => 'acceptance',
      'path' => '',
    ])->save();
    $this->assertTrue($entity->get('oe_media_js_asset_url')->isEmpty());
    $entity->set('oe_media_js_asset_url', [
      'environment' => 'acceptance',
      'path' => '',
    ])->save();
    $this->assertTrue($entity->get('oe_media_js_asset_url')->isEmpty());
  }

}
