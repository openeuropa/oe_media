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
      'access media_entity_browser entity browser pages',
    ]);

    $this->drupalLogin($editor);
    // There are permission issues with the Docker container
    // so we need to manually change the permissions to allow file uploads.
    exec('chmod -R 777 ' . $this->publicFilesDirectory);
    exec('chmod -R 777 ' . $this->tempFilesDirectory);
  }

  /**
   * Create a Media Image entity.
   *
   * @param string $name
   *   The name of the video.
   * @param string $file_source
   *   The contents of the file.
   */
  public function createMediaImageEntity(string $name, string $file_source): void {
    $file = file_save_data(file_get_contents($file_source), 'public://' . basename($file_source));
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $this->container->get('entity_type.manager');
    $entityTypeManager->getStorage('media')->create([
      'bundle' => 'image',
      'name' => $name,
      'oe_media_image' => [
        'target_id' => $file->id(),
      ],
    ])->save();
  }

  /**
   * Test the media entity browser.
   */
  public function testMediaBrowser(): void {
    $image_name = 'My Image 1';
    $filename = 'example_1.jpeg';
    $path = drupal_get_path('module', 'oe_media');
    $file_source = $this->root . '/' . $path . '/tests/fixtures/' . $filename;

    $this->createMediaImageEntity($image_name, $file_source);

    // Select media image though entity browser.
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField("title[0][value]", 'My Node');
    $this->click('#edit-field-oe-demo-media-browser-wrapper');
    $this->getSession()->getPage()->pressButton('Select entities');

    // Go to modal window.
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
    $this->getSession()->wait(3000);
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
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->addressEquals('/node/1');
    $this->assertSession()->elementAttributeContains('css', '.field--name-oe-media-image>img', 'src', $filename);
  }

}
