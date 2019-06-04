<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_embed\Traits;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;

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
  public function getEmbedDialog($filter_format_id = NULL, $embed_button_id = NULL): string {
    $url = 'media-embed/dialog';
    if (!empty($filter_format_id)) {
      $url .= '/' . $filter_format_id;
      if (!empty($embed_button_id)) {
        $url .= '/' . $embed_button_id;
      }
    }
    return $this->drupalGet($url);
  }

}
