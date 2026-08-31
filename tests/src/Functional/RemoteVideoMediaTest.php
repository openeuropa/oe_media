<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\Functional;

/**
 * Tests the creation and the referencing of remote video media entities.
 *
 * @group oe_media
 * @group batch3
 */
class RemoteVideoMediaTest extends MediaFeatureTestBase {

  /**
   * The remote videos to test, keyed by URL.
   *
   * @var string[]
   */
  protected const REMOTE_VIDEOS = [
    'https://www.youtube.com/watch?v=1-g73ty9v04' => "Energy, let's save it!",
    'https://vimeo.com/7073899' => 'Drupal Rap Video - Schipulcon09',
    'https://www.dailymotion.com/video/x6pa0tr' => 'European Commission Fines Google',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->enableMediaStandaloneUrl();
    $this->drupalLogin($this->drupalCreateUser([], '', TRUE));
  }

  /**
   * Tests that remote videos can be referenced and attached to nodes.
   */
  public function testRemoteVideoReference(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    foreach (self::REMOTE_VIDEOS as $url => $title) {
      $this->drupalGet('media/add/remote_video');
      $assert_session->pageTextContains('Add Remote video');
      $page->fillField('Remote video URL', $url);
      $page->pressButton('Save');
      $assert_session->pageTextContains($title);

      $this->drupalGet('node/add/oe_media_demo');
      $assert_session->pageTextContains('Create OpenEuropa Media Demo');
      $page->fillField('Title', 'My Node');
      $page->fillField('field_oe_demo_remote_video_media[0][target_id]', $title);
      $page->pressButton('Save');

      $assert_session->pageTextContains('My Node');
      $this->assertOembedIframeRendered($url);
    }
  }

}
