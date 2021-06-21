<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_js_asset\Functional;

use Drupal\Tests\media\Functional\MediaFunctionalTestBase;

/**
 * Tests JavaScript asset media.
 *
 * @group oe_media_js_asset
 */
class JavaScriptAssetTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_media_js_asset',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the JavaScript asset media item.
   */
  public function testJavascriptAssetMediaItem(): void {
    $user = $this->createUser([], '', TRUE);
    $this->drupalLogin($user);

    $this->drupalGet('/media/add/javascript_asset');
    $this->assertSession()->fieldExists('Name');
    $this->assertSession()->fieldExists('Environment');
    $this->assertSession()->fieldExists('JavaScript relative path');

    $page = $this->getSession()->getPage();
    $page->fillField('Name', 'First JavaScript asset');
    $page->selectFieldOption('Environment', 'Acceptance');
    $page->fillField('JavaScript relative path', '/somejavascript.js');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('JavaScript asset First JavaScript asset has been created.');

    // Load the media and assert for field values.
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['name' => 'First JavaScript asset']);
    $media = reset($media);
    $this->assertEquals('First JavaScript asset', $media->label());
    $this->assertEquals('acceptance', $media->get('oe_media_js_asset_url')->environment);
    $this->assertEquals('/somejavascript.js', $media->get('oe_media_js_asset_url')->path);
  }

}
