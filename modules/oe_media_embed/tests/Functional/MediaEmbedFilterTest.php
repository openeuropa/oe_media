<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_embed\Functional;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests the oEmbed media filter.
 */
class MediaEmbedFilterTest extends MediaEmbedTestBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $format = FilterFormat::create([
      'format' => 'format_with_embed',
      'name' => 'Format with embed',
      'filters' => [
        'media_embed' => [
          'status' => 1,
        ],
      ],
    ]);
    $format->save();

    $editor_group = [
      'name' => 'Media Embed',
      'items' => [
        'media',
      ],
    ];

    $editor = Editor::create([
      'format' => 'format_with_embed',
      'editor' => 'ckeditor',
      'settings' => [
        'toolbar' => [
          'rows' => [[$editor_group]],
        ],
      ],
    ]);
    $editor->save();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->createTestMediaEntities();
  }

  /**
   * Tests that the media oEmbed filter correctly renders the oEmbed tags.
   */
  public function testEmbedFilter(): void {
    $assert_session = $this->assertSession();

    // Remote video media.
    $media = $this->entityTypeManager->getStorage('media')->loadByProperties(['bundle' => 'remote_video']);
    $media = reset($media);
    $content = '<p data-oembed="https://oembed.ec.europa.eu?url=https%3A//data.ec.europa.eu/ewp/media/' . $media->uuid() . '"><a href="https://data.ec.europa.eu/ewp/media/' . $media->uuid() . '">Digital Single Market: cheaper calls to other EU countries as of 15 May</a></p>';
    $values = [];
    $values['type'] = 'page';
    $values['title'] = 'Test video embed node';
    $values['body'] = [['value' => $content, 'format' => 'format_with_embed']];
    $node = $this->drupalCreateNode($values);
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.field--name-oe-media-oembed-video');
    $assert_session->elementExists('css', 'iframe.media-oembed-content');
    $assert_session->elementAttributeContains('css', 'iframe.media-oembed-content', 'src', 'media/oembed?url=https%3A//www.youtube.com/watch%3Fv%3DOkPW9mK5Vw8');

    // Image media.
    $media = $this->entityTypeManager->getStorage('media')->loadByProperties(['bundle' => 'image']);
    $media = reset($media);
    $content = '<p data-oembed="https://oembed.ec.europa.eu?url=https%3A//data.ec.europa.eu/ewp/media/' . $media->uuid() . '"><a href="https://data.ec.europa.eu/ewp/media/' . $media->uuid() . '">Test image media</a></p>';
    $values = [];
    $values['type'] = 'page';
    $values['title'] = 'Test image embed node';
    $values['body'] = [['value' => $content, 'format' => 'format_with_embed']];
    $node = $this->drupalCreateNode($values);
    $this->drupalGet('node/' . $node->id());
    // Check that the media element got rendered.
    $assert_session->elementAttributeContains('css', 'img', 'src', 'files/example_1.jpeg');

    // Document media.
    $media = $this->entityTypeManager->getStorage('media')->loadByProperties(['bundle' => 'document']);
    $media = reset($media);
    $content = '<p data-oembed="https://oembed.ec.europa.eu?url=https%3A//data.ec.europa.eu/ewp/media/' . $media->uuid() . '"><a href="https://data.ec.europa.eu/ewp/media/' . $media->uuid() . '">Test document media</a></p>';
    $values = [];
    $values['type'] = 'page';
    $values['title'] = 'Test document embed node';
    $values['body'] = [['value' => $content, 'format' => 'format_with_embed']];
    $node = $this->drupalCreateNode($values);
    $this->drupalGet('node/' . $node->id());
    // Check that the media element got rendered.
    $assert_session->elementExists('css', '.media--type-document');
    $assert_session->linkExists('sample.pdf');
    $assert_session->elementAttributeContains('css', '.field--name-oe-media-file a', 'href', 'sample.pdf');
  }

}
