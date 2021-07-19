<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_js_asset\Functional;

/**
 * Tests the oe_media_js_asset_url widget.
 *
 * @group oe_media_js_asset
 */
class JavaScriptAssetUrlWidgetTest extends JavaScriptAssetTestBase {

  /**
   * Tests the widget form element.
   */
  public function testWidgetElements(): void {
    $this->drupalGet('/media/add/javascript_asset');

    $this->assertSession()->fieldExists('Environment');
    $this->assertSession()->optionExists('Environment', 'Production');
    $this->assertSession()->optionExists('Environment', 'Acceptance');
    $this->assertSession()->pageTextContains('A relative path to the JS asset. It should always start with a "/" character.');
    $this->assertSession()->fieldExists('Path');

    // Add the relative path without '/' and assert the form error.
    $page = $this->getSession()->getPage();
    $page->fillField('Name', 'First JavaScript asset');
    $page->selectFieldOption('Environment', 'Acceptance');
    $page->fillField('Path', 'somejavascript.js');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('Path should start with: /');

    $page->fillField('Path', '/some>javascript.js');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('The entered path is not valid.');

    $page->fillField('Path', '/somejavascript.js');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('JavaScript asset First JavaScript asset has been created.');
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['name' => 'First JavaScript asset']);
    $media = reset($media);
    $this->assertEquals('acceptance', $media->get('oe_media_js_asset_url')->environment);
    $this->assertEquals('/somejavascript.js', $media->get('oe_media_js_asset_url')->path);
  }

}