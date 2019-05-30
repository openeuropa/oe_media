<?php

namespace Drupal\oe_media_embed\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\embed\EmbedButtonInterface;
use Drupal\embed\EmbedCKEditorPluginBase;

/**
 * Defines the plugin responsible for embedding Media entities
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
  protected function getButton(EmbedButtonInterface $embed_button) {
    $button = parent::getButton($embed_button);
    $button['media_type'] = $embed_button->getTypeSetting('media_type');
    return $button;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'oe_media_embed') . '/js/plugins/embed_media/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'Media_buttons' => $this->getButtons(),
    ];
  }

}
