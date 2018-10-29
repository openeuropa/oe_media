<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests that we can create and use Remote video media entities.
 *
 * @group oe_media
 */
class MediaRemoteVideoTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'oe_media_demo',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $editor = $this->drupalCreateUser([
      'create oe_media_demo content',
      'create remote_video media',
    ]);

    $this->drupalLogin($editor);

    // There are permission issues with the Docker container
    // so we need to manually change the permissions to allow file uploads.
    exec('chmod -R 777 ' . $this->publicFilesDirectory);
  }

  /**
   * Test the creation of Media Remote video entity and reuse on the Demo node.
   */
  public function testCreateRemoteVideoMedia(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Create a media item.
    $this->drupalGet("media/add/remote_video");

    $page->fillField("oe_media_oembed_video[0][value]", 'https://www.youtube.com/watch?v=1-g73ty9v04');
    $page->pressButton('Save');
    $assert_session->addressEquals('/media/1');

    // Create a node with attached media.
    $this->drupalGet("node/add/oe_media_demo");
    $page->fillField("title[0][value]", 'My Node');
    $autocomplete_field = $page->findField('field_oe_demo_remote_video_media[0][target_id]');
    $autocomplete_field->setValue('Energy, let\'s save it!');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();
    $this->getSession()->getDriver()->click($page->find('css', '.ui-autocomplete li')->getXpath());
    $page->pressButton('Save');
    $assert_session->addressEquals('/node/1');
  }

}
