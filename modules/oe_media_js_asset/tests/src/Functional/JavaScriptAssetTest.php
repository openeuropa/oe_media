<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_js_asset\Functional;

use Drupal\media\Entity\Media;

/**
 * Tests JavaScript asset media.
 *
 * @group oe_media_js_asset
 */
class JavaScriptAssetTest extends JavaScriptAssetTestBase {

  /**
   * Tests the JavaScript asset media item.
   */
  public function testJavaScriptAssetMediaItem(): void {
    $js_asset = Media::create([
      'name' => 'First JavaScript asset',
      'bundle' => 'javascript_asset',
      'oe_media_js_asset_url' => [
        'environment' => 'acceptance',
        'path' => '/somejavascript.js',
      ],
    ]);
    $js_asset->save();

    // Load the media and assert for field values.
    $media = \Drupal::entityTypeManager()->getStorage('media')->load($js_asset->id());
    $this->assertEquals('First JavaScript asset', $media->label());
    $this->assertEquals('acceptance', $media->get('oe_media_js_asset_url')->environment);
    $this->assertEquals('/somejavascript.js', $media->get('oe_media_js_asset_url')->path);
  }

}
