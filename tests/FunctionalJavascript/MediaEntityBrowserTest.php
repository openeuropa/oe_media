<?php

namespace Drupal\Tests\oe_media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * A test for the media entity browser.
 *
 * @group media_entity_browser
 */
class MediaEntityBrowserTest extends WebDriverTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'oe_media_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $editor = $this->drupalCreateUser([
      'create oe_media_test content',
      'create image media',
      'access media_entity_browser entity browser pages',
    ]);

    $this->drupalLogin($editor);
    // There are permission issues with the Docker container
    // so we need to manually change the permissions to allow file uploads.
    exec('chmod -R 777 ' . $this->publicFilesDirectory);
    exec('chmod -R 777 ' . $this->tempFilesDirectory);

  }

  /**
   * Test the media entity browser.
   */
  public function testMediaBrowser() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();
    $image_name = 'My Image 1';
    $filename = 'example_1.jpeg';

    // Create a media item.
    $this->drupalGet("media/add/image");
    $page->fillField("name[0][value]", $image_name);
    $path = drupal_get_path('module', 'oe_media');
    $page->attachFileToField("files[oe_media_image_0]", $path . '/tests/fixtures/' . $filename);
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->fillField("oe_media_image[0][alt]", 'Image Alt Text 1');
    $page->pressButton('Save');
    $assert_session->addressEquals('/media/1');

    // Select media image though entity browser.
    $this->drupalGet('node/add/oe_media_test');
    $page->fillField("title[0][value]", 'My Node');
    $this->click('#edit-field-media-test-wrapper');
    $page->pressButton('Select entities');

    // Go to modal window.
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
    $this->assertSession()->elementExists('css', '.js-form-item-status select');
    $this->assertSession()->elementExists('css', '.js-form-item-provider select');
    $this->assertSession()->elementExists('css', '.js-form-item-name input');
    $this->assertSession()->elementExists('css', '.js-form-item-langcode select');
    $iframe_page = $this->getSession()->getPage();
    $iframe_page->checkField('entity_browser_select[media:1]');
    $iframe_page->pressButton('Select entities');

    // Go back to main window.
    $this->getSession()->switchToIFrame();
    $assert_session->waitForButton('Remove');
    $page->pressButton('Save');
    $this->createScreenshot($this->root . '/screenshot.jpg');
    $assert_session->addressEquals('/node/1');
    $assert_session->elementAttributeContains('css', '.field--name-oe-media-image>img', 'src', $filename);
  }

}
