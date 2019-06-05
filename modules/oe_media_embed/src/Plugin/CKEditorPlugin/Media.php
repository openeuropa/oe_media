<?php

declare(strict_types = 1);

namespace Drupal\oe_media_embed\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\embed\EmbedCKEditorPluginBase;

/**
 * Defines the plugin responsible for embedding Media entities.
 *
 * @CKEditorPlugin(
 *   id = "embed_media",
 *   label = @Translation("Media"),
 *   embed_type_id = "embed_media"
 * )
 */
class Media extends EmbedCKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile(): string {
    return drupal_get_path('module', 'oe_media_embed') . '/js/plugins/embed_media/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor): array {
    return [
      'Media_buttons' => $this->getButtons(),
    ];
  }

}
