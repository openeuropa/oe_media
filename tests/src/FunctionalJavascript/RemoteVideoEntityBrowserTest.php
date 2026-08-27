<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\FunctionalJavascript;

/**
 * Tests the remote video widgets of the media entity browser.
 *
 * @group oe_media
 * @group batch2
 */
class RemoteVideoEntityBrowserTest extends MediaFeatureTestBase {

  /**
   * The remote videos to test, keyed by URL.
   *
   * @var string[]
   */
  protected const REMOTE_VIDEOS = [
    'https://www.dailymotion.com/video/x6pa0tr' => 'European Commission Fines Google',
    'https://www.youtube.com/watch?v=1-g73ty9v04' => "Energy, let's save it!",
    'https://vimeo.com/7073899' => 'Drupal Rap Video - Schipulcon09',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'access content',
      'create oe_media_demo content',
      'create remote_video media',
      'access media_entity_browser entity browser pages',
      'view media',
    ]));
  }

  /**
   * Tests that remote videos can be added and referenced in the browser.
   */
  public function testRemoteVideoEntityBrowser(): void {
    $assert_session = $this->assertSession();

    foreach (self::REMOTE_VIDEOS as $url => $title) {
      $this->drupalGet('node/add/oe_media_demo');
      $assert_session->pageTextContains('Create OpenEuropa Media Demo');
      $this->getSession()->getPage()->fillField('Title', 'Videos are awesome');

      $this->openEntityBrowser();
      $this->clickEntityBrowserTab('Add Video');
      $browser_page = $this->getSession()->getPage();
      $browser_page->fillField('Remote video URL', $url);
      $browser_page->pressButton('Save entity');
      $this->waitForEntityBrowserToClose();

      $this->assertNotEmpty($assert_session->waitForText($title));
      $assert_session->buttonExists('Remove');
      $this->getSession()->getPage()->pressButton('Save');
      $assert_session->pageTextContains('Videos are awesome');
      $this->assertOembedIframeRendered($url);

      // Reuse the video that was just created on another node.
      $this->drupalGet('node/add/oe_media_demo');
      $assert_session->pageTextContains('Create OpenEuropa Media Demo');
      $this->getSession()->getPage()->fillField('Title', 'More videos');

      $this->openEntityBrowser();
      $this->selectMediaInEntityBrowser($title);
      $this->getSession()->getPage()->pressButton('Select entities');
      $this->waitForEntityBrowserToClose();

      $this->assertNotEmpty($assert_session->waitForText($title));
      $assert_session->buttonExists('Remove');
      $this->getSession()->getPage()->pressButton('Save');
      $assert_session->pageTextContains('More videos');
      $this->assertOembedIframeRendered($url);
    }
  }

}
