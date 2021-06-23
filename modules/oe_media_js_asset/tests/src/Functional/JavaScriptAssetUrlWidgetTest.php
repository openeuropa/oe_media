<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_js_asset\Functional;

/**
 * Tests the javascript_asset_url widget.
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

    $this->assertSession()->pageTextContains('Paths should start with: /');

    $page->fillField('Path', '/somejavascript.js');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('JavaScript asset First JavaScript asset has been created.');
  }

}
