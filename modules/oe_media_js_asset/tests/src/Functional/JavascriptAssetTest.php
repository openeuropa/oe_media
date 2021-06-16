<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_js_asset\Functional;

use Drupal\Tests\media\Functional\MediaFunctionalTestBase;

/**
 * Tests javascript asset media.
 *
 * @group oe_media_js_asset
 */
class JavascriptAssetTest extends MediaFunctionalTestBase {

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
   * Tests the javascript asset media item.
   */
  public function testJavascriptAssetMediaItem(): void {
    $user = $this->createUser([], '', TRUE);
    $this->drupalLogin($user);

    $this->drupalGet('/media/add/javascript_asset');
    $this->assertSession()->fieldExists('Name');
    $this->assertSession()->fieldExists('Environment');
    $this->assertSession()->fieldExists('Javascript relative path');

    $page = $this->getSession()->getPage();
    $page->fillField('Name', 'First javascript asset');
    $page->selectFieldOption('Environment', 'Acceptance');
    $page->fillField('Javascript relative path', '/somejavascript.js');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('Javascript asset First javascript asset has been created.');

    // Load the media and assert for field values.
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['name' => 'First javascript asset']);
    $media = reset($media);
    $this->assertEquals('First javascript asset', $media->label());
    $this->assertEquals('https://acceptance.europa.eu/webassets', $media->get('oe_media_js_asset_url')->environment);
    $this->assertEquals('/somejavascript.js', $media->get('oe_media_js_asset_url')->relative_path);
  }

}
