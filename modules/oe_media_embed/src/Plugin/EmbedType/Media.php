<?php

namespace Drupal\oe_media_embed\Plugin\EmbedType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\embed\EmbedType\EmbedTypeBase;

/**
 * Embeds Media entities in a Drupal-agnostic way.
 *
 * @EmbedType(
 *   id = "embed_media",
 *   label = @Translation("Media"),
 * )
 */
class Media extends EmbedTypeBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'media_type' => 'image',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['media_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Media type'),
      '#options' => [
        'image' => $this->t('Image'),
      ],
      '#default_value' => $this->getConfigurationValue('media_type'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultIconUrl() {
    return '';
  }

}
