<?php

declare(strict_types=1);

namespace Drupa\Tests\oe_media_gallery\Kernel;

use Drupal\Tests\oe_media\Kernel\MediaTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\oe_link_lists\Entity\LinkList;
use Drupal\oe_link_lists\EntityAwareLinkInterface;

/**
 * Tests the Gallery link list type.
 *
 * @covers \Drupal\oe_media_gallery\Plugin\LinkSource\DefaultSource
 */
class GalleryTest extends MediaTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_link_lists',
    'oe_media_gallery',
    'field',
    'user',
    'oe_media_oembed_mock',
  ];

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

    $this->installEntitySchema('link_list');
    $this->installConfig([
      'field',
      'system',
      'oe_media_gallery',
    ]);

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
      'oe_media_oembed_video' => 'https://www.youtube.com/watch?v=1-g73ty9v04',
    ]);
    $this->videoMedia->save();
  }

  /**
   * Tests the getLinks() method.
   *
   * @covers ::getLinks
   */
  public function testGetLinks(): void {
    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $list */
    $list = LinkList::create([
      'bundle' => 'gallery',
      'oe_media_gallery_media' => [
        $this->imageMedia->id(),
        $this->videoMedia->id(),
      ],
      'status' => 1,
    ]);
    $list->setConfiguration([
      'source' => [
        'plugin' => 'oe_media_gallery_default',
        // We keep empty to assert also the presave kicks in and moves the
        // referenced entities to the plugin config.
        'plugin_configuration' => [],
      ],
    ]);
    $list->save();

    $plugin_manager = $this->container->get('plugin.manager.oe_link_lists.link_source');
    /** @var \Drupal\oe_link_lists_manual_source\Plugin\LinkSource\ManualLinkSource $plugin */
    $plugin_configuration = $list->getConfiguration()['source']['plugin_configuration'];
    $plugin = $plugin_manager->createInstance('oe_media_gallery_default', $plugin_configuration);

    $links = $plugin->getLinks();
    $this->assertCount(2, $links);
    /** @var \Drupal\oe_link_lists\LinkInterface[] $categorised */
    $categorised = [];
    foreach ($links as $link) {
      $this->assertInstanceOf(EntityAwareLinkInterface::class, $link);
      /** @var \Drupal\media\MediaInterface $entity */
      $entity = $link->getEntity();
      $this->assertInstanceOf(MediaInterface::class, $entity);
      $categorised[$entity->bundle()] = $link;
    }

    $this->assertEquals('Image media title', $categorised['image']->getTitle());
    $this->assertEquals(['#markup' => ''], $categorised['image']->getTeaser());

    $this->assertEquals('Energy, let\'s save it!', $categorised['remote_video']->getTitle());
    $this->assertEquals(['#markup' => ''], $categorised['remote_video']->getTeaser());

    // Assert cache metadata.
    $this->assertEquals([
      'media:1',
      'media:2',
    ], $links->getCacheTags());

    // Assert we can filter the amount of links we get.
    $links = $plugin->getLinks(1);
    $this->assertCount(1, $links);
    $this->assertEquals('image', $links[0]->getEntity()->bundle());

    // Verify the offset.
    $links = $plugin->getLinks(1, 1);
    $this->assertCount(1, $links);
    $this->assertEquals('remote_video', $links[0]->getEntity()->bundle());
  }

}
