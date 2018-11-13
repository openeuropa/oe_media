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
   * Data provider for testRemoteVideoMedia().
   */
  public function providerRemoteVideoMedia() {
    return [
      'Youtube' => [
        'https://www.youtube.com/watch?v=1-g73ty9v04',
        'Energy, let\'s save it!',
      ],
      'Vimeo' => [
        'https://vimeo.com/7073899',
        'Drupal Rap Video - Schipulcon09',
      ],
      'Dailymotion' => [
        'http://www.dailymotion.com/video/x6pa0tr',
        'European Commission Fines Google',
      ],
    ];
  }

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
  }

  /**
   * Test the creation of Media Remote video entity and reuse on the Demo node.
   *
   * @param string $video_url
   *   Video URL.
   * @param string $video_name
   *   Name of Video.
   *
   * @dataProvider providerRemoteVideoMedia
   */
  public function testRemoteVideoMedia(string $video_url, string $video_name): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Create a media item.
    $this->drupalGet("media/add/remote_video");
    $page->fillField("oe_media_oembed_video[0][value]", $video_url);
    $page->pressButton('Save');
    $assert_session->addressEquals('/media/1');

    // Create a node with attached media.
    $this->drupalGet("node/add/oe_media_demo");
    $page->fillField("title[0][value]", 'My Node');
    $autocomplete_field = $page->findField('field_oe_demo_remote_video_media[0][target_id]');
    $autocomplete_field->setValue($video_name);
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();
    $this->getSession()->getDriver()->click($page->find('css', '.ui-autocomplete li')->getXpath());
    $page->pressButton('Save');
    $assert_session->addressEquals('/node/1');

    // Ensure the iframe exists and that its src attribute contains a coherent
    // URL with the query parameters we expect.
    $iframe_url = $assert_session->elementExists('css', 'iframe')->getAttribute('src');
    $iframe_url = parse_url($iframe_url);
    $this->assertStringEndsWith('/media/oembed', $iframe_url['path']);
    $this->assertNotEmpty($iframe_url['query']);
    $query = [];
    parse_str($iframe_url['query'], $query);
    $this->assertSame($video_url, $query['url']);
    $this->assertNotEmpty($query['hash']);
  }

}
