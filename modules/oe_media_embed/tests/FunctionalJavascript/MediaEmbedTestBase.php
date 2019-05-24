<?php

namespace Drupal\Tests\oe_media_embed\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\oe_media_embed\Traits\MediaEmbedTrait;

/**
 * Base class for all functional JS media embed tests.
 */
abstract class MediaEmbedTestBase extends WebDriverTestBase {

  use MediaEmbedTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'oe_media',
    'oe_media_embed',
    'oe_media_embed_test',
    'node',
    'ckeditor',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->basicSetup();

    // Create an image media.
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet('media/add/image');
    $page->fillField('name[0][value]', 'My image media');
    $path = drupal_get_path('module', 'oe_media');
    $page->attachFileToField('files[oe_media_image_0]', $this->root . '/' . $path . '/tests/fixtures/example_1.jpeg');
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->fillField('oe_media_image[0][alt]', 'Image Alt Text 1');
    $page->pressButton('Save');

    // Create a video media.
    // The title is "Digital Single Market: cheaper calls to other EU countries
    // as of 15 May"
    $this->drupalGet('media/add/remote_video');
    $page->fillField('oe_media_oembed_video[0][value]', 'https://www.youtube.com/watch?v=OkPW9mK5Vw8');
    $page->pressButton('Save');
  }
}
