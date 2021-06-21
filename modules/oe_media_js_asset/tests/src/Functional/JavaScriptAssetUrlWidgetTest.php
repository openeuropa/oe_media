<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_js_asset\Functional;

use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\Tests\oe_media\Traits\MediaTypeCreationTrait;

/**
 * Tests the javascript_asset_url widget.
 *
 * @group oe_media_js_asset
 */
class JavaScriptAssetUrlWidgetTest extends MediaFunctionalTestBase {

  use MediaTypeCreationTrait;

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
   * Tests the widget form element.
   */
  public function testWidgetElements(): void {
    $this->drupalGet('/media/add/javascript_asset');

    $this->assertSession()->fieldExists('Environment');
    $this->assertSession()->optionExists('Environment', 'Production');
    $this->assertSession()->optionExists('Environment', 'Acceptance');
    $this->assertSession()->fieldExists('JavaScript relative path');

    // Add the relative path without '/' and assert the form error.
    $page = $this->getSession()->getPage();
    $page->fillField('Name', 'First JavaScript asset');
    $page->selectFieldOption('Environment', 'Acceptance');
    $page->fillField('JavaScript relative path', '');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('Manually entered paths should start with: /');

    $page->fillField('JavaScript relative path', '/somejavascript.js');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('JavaScript asset First JavaScript asset has been created.');
  }

}
