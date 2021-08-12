<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_js_asset\Functional;

/**
 * Test javascript asset media.
 */
class JavaScriptAssetTest extends JavaScriptAssetTestBase {

  /**
   * Tests javascript asset media.
   */
  public function testJavascriptAssetMedia(): void {
    $user = $this->createUser([], '', TRUE);
    $this->drupalLogin($user);

    $this->drupalGet('/media/add/javascript_asset');
    $this->assertSession()->fieldExists('Name');
    $this->assertSession()->fieldExists('Environment');
    $this->assertSession()->optionExists('Environment', 'Production');
    $this->assertSession()->optionExists('Environment', 'Acceptance');
    $this->assertSession()->pageTextContains('A relative path to the JS asset. It should always start with a "/" character.');
    $this->assertSession()->fieldExists('Path');

    $page = $this->getSession()->getPage();
    $page->fillField('Name', 'First JavaScript asset');
    $page->fillField('Environment', 'acceptance');
    $page->fillField('Path', 'somejavascript.js');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('Path should start with: /');
    $this->assertSession()->pageTextContains('Path should start with: /');

    $page->fillField('Path', '/some>javascript.js');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('The entered path is not valid.');

    $page->fillField('Path', '/somejavascript.js');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('Javascript asset First JavaScript asset has been created.');

    // Edit the media.
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['name' => 'First JavaScript asset']);
    $media = reset($media);
    $this->drupalGet($media->toUrl());

    $assert_session = $this->assertSession();
    $this->assertEquals('First JavaScript asset', $assert_session->fieldExists('Name')->getValue());
    $this->assertEquals('/somejavascript.js', $assert_session->fieldExists('Path')->getValue());
    $this->assertEquals('acceptance', $assert_session->fieldExists('Environment')->getValue());
    $page->fillField('Environment', 'production');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('Javascript asset First JavaScript asset has been updated.');
  }

}
