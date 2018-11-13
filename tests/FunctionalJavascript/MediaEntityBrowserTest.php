<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * A test for the media entity browser.
 *
 * @group oe_media
 */
class MediaEntityBrowserTest extends WebDriverTestBase {

  /**
   * Modules to install.
   *
   * @var array
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
      'create image media',
      'create remote_video media',
      'access media_entity_browser entity browser pages',
    ]);

    $this->drupalLogin($editor);
  }

  /**
   * Data provider for testMediaBrowserWithRemoteVideo().
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
   * Test the media entity browser with a remote video.
   *
   * @param string $video_url
   *   Video URL.
   *
   * @dataProvider providerRemoteVideoMedia
   */
  public function testMediaBrowserWithRemoteVideo(string $video_url): void {
    $this->createRemoteVideoMedia($video_url);
    $this->checkMediaBrowserMediaSelection();

    // Ensure the iframe exists and that its src attribute contains a coherent
    // URL with the query parameters we expect.
    $iframe_url = $this->assertSession()->elementExists('css', 'iframe')->getAttribute('src');
    $iframe_url = parse_url($iframe_url);
    $this->assertStringEndsWith('/media/oembed', $iframe_url['path']);
    $this->assertNotEmpty($iframe_url['query']);
    $query = [];
    parse_str($iframe_url['query'], $query);
    $this->assertSame($video_url, $query['url']);
    $this->assertNotEmpty($query['hash']);
  }

  /**
   * Test the media entity browser with the image.
   */
  public function testMediaBrowserWithImage(): void {
    $filename = 'example_1.jpeg';
    $path = drupal_get_path('module', 'oe_media');
    $file_source = $this->root . '/' . $path . '/tests/fixtures/' . $filename;

    $this->createImageMedia($filename, $file_source);
    $this->checkMediaBrowserMediaSelection();

    $this->assertSession()->elementAttributeContains('css', '.field--name-oe-media-image>img', 'src', $filename);
  }

  /**
   * Generic helper that tests the entity browser media select functionality.
   */
  protected function checkMediaBrowserMediaSelection() {
    // Select media image though entity browser.
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField("title[0][value]", $this->randomString());
    $this->click('#edit-field-oe-demo-media-browser-wrapper');
    $this->getSession()->getPage()->pressButton('Select entities');

    // Go to modal window.
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $iframe_page = $this->getSession()->getPage();
    $iframe_page->hasSelect('Publishing status');
    $iframe_page->hasSelect('Media type');
    $iframe_page->hasField('Media name');
    $iframe_page->hasSelect('Language');
    $iframe_page->findField('edit-entity-browser-select-media1')->click();
    $iframe_page->pressButton('Select entities');

    // Go back to main window.
    $this->getSession()->switchToIFrame();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->waitForButton('Remove');
    // Save node.
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->addressEquals('/node/1');
  }

  /**
   * Create a Media Remote video entity.
   *
   * @param string $video_url
   *   The URL of the video.
   */
  protected function createRemoteVideoMedia(string $video_url): void {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $this->container->get('entity_type.manager');
    $entityTypeManager->getStorage('media')->create([
      'bundle' => 'remote_video',
      'oe_media_oembed_video' => $video_url,
    ])->save();
  }

  /**
   * Create a Media Image entity.
   *
   * @param string $name
   *   The name of the image file.
   * @param string $file_source
   *   The contents of the file.
   */
  protected function createImageMedia(string $name, string $file_source): void {
    $file = file_save_data(file_get_contents($file_source), 'public://' . $name);
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $this->container->get('entity_type.manager');
    $entityTypeManager->getStorage('media')->create([
      'bundle' => 'image',
      'oe_media_image' => [
        'target_id' => $file->id(),
      ],
    ])->save();
  }

}
