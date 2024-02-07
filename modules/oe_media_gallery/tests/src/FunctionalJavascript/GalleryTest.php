<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_gallery\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\oe_link_lists\Traits\LinkListTestTrait;

/**
 * Tests the Gallery link list bundle.
 */
class GalleryTest extends WebDriverTestBase {

  use LinkListTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'oe_media',
    'oe_media_avportal',
    'oe_media_iframe',
    'file',
    'oe_link_lists',
    'oe_link_lists_test',
    'oe_media_gallery',
    'oe_media_gallery_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A test image media.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $imageMedia;

  /**
   * A test video media.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $videoMedia;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an image and a remote video media.
    $image = \Drupal::service('file.repository')->writeData(
      file_get_contents(\Drupal::service('extension.list.module')->getPath('oe_media') . '/tests/fixtures/example_1.jpeg'),
      'public://example_1.jpeg'
    );
    $image->setPermanent();
    $image->save();

    $this->imageMedia = Media::create([
      'bundle' => 'image',
      'name' => 'Image media title',
      'oe_media_image' => [
        [
          'target_id' => $image->id(),
          'alt' => 'default alt',
        ],
      ],
    ]);
    $this->imageMedia->save();

    $this->videoMedia = Media::create([
      'bundle' => 'remote_video',
      'oe_media_oembed_video' => 'https://www.youtube.com/watch?v=OkPW9mK5Vw8',
    ]);
    $this->videoMedia->save();

    $this->drupalLogin($this->drupalCreateUser([], '', TRUE));
  }

  /**
   * Tests the Gallery link list bundle.
   */
  public function testGalleryLinkList(): void {
    // Assert we didn't accidentally remove configuration form elements on
    // other bundles.
    $this->drupalGet('link_list/add/dynamic');
    $this->assertSession()->fieldExists('Number of items');

    $this->drupalGet('link_list/add/gallery');
    $this->assertSession()->fieldNotExists('Number of items');

    // Assert we can only see the source plugins that work with the Gallery
    // bundle (or that don't have bundle restrictions).
    $this->assertFieldSelectOptions('Link source', [
      'oe_media_gallery_default',
      'test_no_bundle_restriction_source',
    ]);

    // Assert we can only see the display plugins that work with the Gallery
    // bundle (or that don't have bundle restrictions).
    $this->assertFieldSelectOptions('Link display', [
      'oe_media_gallery_default',
      'test_no_bundle_restriction_display',
    ]);

    $this->getSession()->getPage()->fillField('Administrative title', 'The admin title');
    $this->getSession()->getPage()->fillField('Title', 'The title');

    $this->assertSession()->fieldNotExists('View mode');

    // Select the link source and display.
    $this->getSession()->getPage()->selectFieldOption('Link source', 'Media');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('Link display', 'Default');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldExists('View mode');
    $this->getSession()->getPage()->selectFieldOption('View mode', 'Full content');
    $this->getSession()->getPage()->selectFieldOption('No results behaviour', 'Hide');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Select the media entities.
    $this->getSession()->getPage()->fillField('oe_media_gallery_media[0][target_id]', $this->imageMedia->label() . ' (' . $this->imageMedia->id() . ')');
    $this->getSession()->getPage()->pressButton('Add another item');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->fillField('oe_media_gallery_media[1][target_id]', $this->videoMedia->label() . ' (' . $this->videoMedia->id() . ')');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('Saved the The admin title Link list.');

    /** @var \Behat\Mink\Element\NodeElement[] $items */
    $items = $this->getSession()->getPage()->findAll('css', 'main ul li');
    $this->assertCount(2, $items);
    $this->assertEquals('default alt', $items[0]->find('css', 'img')->getAttribute('alt'));
    $this->assertEquals('media-oembed-content', $items[1]->find('css', 'iframe')->getAttribute('class'));
    $this->assertSession()->pageTextContains('View mode: full');

    // Change the view mode config.
    $this->drupalGet('/link_list/1/edit');
    $this->getSession()->getPage()->selectFieldOption('View mode', 'Default');
    $this->getSession()->getPage()->pressButton('Save');
    $items = $this->getSession()->getPage()->findAll('css', 'main ul li');
    $this->assertCount(2, $items);
    $this->assertEquals('default alt', $items[0]->find('css', 'img')->getAttribute('alt'));
    $this->assertEquals('media-oembed-content', $items[1]->find('css', 'iframe')->getAttribute('class'));
    $this->assertSession()->pageTextContains('View mode: default');
  }

  /**
   * Checks if a select element contains the specified options.
   *
   * @param string $name
   *   The field name.
   * @param array $expected_options
   *   An array of expected options.
   */
  protected function assertFieldSelectOptions(string $name, array $expected_options): void {
    $select = $this->getSession()->getPage()->find('named', [
      'select',
      $name,
    ]);

    if (!$select) {
      $this->fail('Unable to find select ' . $name);
    }

    $options = $select->findAll('css', 'option');
    array_walk($options, function (NodeElement &$option) {
      $option = $option->getValue();
    });
    $options = array_filter($options);
    sort($options);
    sort($expected_options);
    $this->assertSame($options, $expected_options);
  }

}
