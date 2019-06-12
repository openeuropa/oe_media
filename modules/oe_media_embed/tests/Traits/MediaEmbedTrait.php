<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_embed\Traits;

use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\media\Entity\Media;

/**
 * Used across functional and functional JS tests for common tasks.
 */
trait MediaEmbedTrait {

  /**
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Performs the basic setup of the test.
   */
  protected function basicSetup(): void {
    // Create a page content type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $format = FilterFormat::create([
      'format' => 'html',
      'name' => 'Html format',
      'filters' => [
        'filter_align' => [
          'status' => 1,
        ],
        'filter_caption' => [
          'status' => 1,
        ],
        'filter_html_image_secure' => [
          'status' => 1,
        ],
      ],
    ]);
    $format->save();

    $editor_group = [
      'name' => 'Embeds',
      'items' => [
        'media',
      ],
    ];
    $editor = Editor::create([
      'format' => 'html',
      'editor' => 'ckeditor',
      'settings' => [
        'toolbar' => [
          'rows' => [[$editor_group]],
        ],
      ],
    ]);
    $editor->save();

    // Create a user with required permissions.
    $this->user = $this->drupalCreateUser([
      'access content',
      'create page content',
      'use text format html',
      'create image media',
      'create remote_video media',
    ]);

    $this->drupalLogin($this->user);
  }

  /**
   * Retrieves an embed dialog based on given parameters.
   *
   * @param string $filter_format_id
   *   ID of the filter format.
   * @param string $embed_button_id
   *   ID of the embed button.
   *
   * @return string
   *   The retrieved HTML string.
   */
  protected function getEmbedDialog(string $filter_format_id = NULL, string $embed_button_id = NULL): string {
    $url = 'media-embed/dialog';
    if (!empty($filter_format_id)) {
      $url .= '/' . $filter_format_id;
      if (!empty($embed_button_id)) {
        $url .= '/' . $embed_button_id;
      }
    }
    return $this->drupalGet($url);
  }

  /**
   * Creates 3 media entities, one of each type.
   */
  protected function createTestMediaEntities(): void {
    /** @var \Drupal\media\MediaTypeInterface[] $media_types */
    $media_types = $this->container->get('entity_type.manager')->getStorage('media_type')->loadMultiple();

    // Image media.
    $this->container->get('file_system')->copy(drupal_get_path('module', 'oe_oembed') . '/tests/fixtures/example_1.jpeg', 'public://example_1.jpeg');
    $image = File::create([
      'uri' => 'public://example_1.jpeg',
    ]);
    $image->save();

    $media_type = $media_types['image'];
    $media_source = $media_type->getSource();
    $source_field = $media_source->getSourceFieldDefinition($media_type);
    $media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Test image media',
      $source_field->getName() => [$image],
    ]);

    $media->save();

    // Remote video media.
    $media_type = $media_types['remote_video'];
    $media_source = $media_type->getSource();
    $source_field = $media_source->getSourceFieldDefinition($media_type);
    $media = Media::create([
      'bundle' => $media_type->id(),
      $source_field->getName() => 'https://www.youtube.com/watch?v=OkPW9mK5Vw8',
    ]);

    $media->save();

    // File media.
    $this->container->get('file_system')->copy(drupal_get_path('module', 'oe_oembed') . '/tests/fixtures/sample.pdf', 'public://sample.pdf');
    $file = File::create([
      'uri' => 'public://sample.pdf',
    ]);
    $file->save();

    $media_type = $media_types['document'];
    $media_source = $media_type->getSource();
    $source_field = $media_source->getSourceFieldDefinition($media_type);
    $media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Test document media',
      $source_field->getName() => [$file],
    ]);

    $media->save();
  }

}
