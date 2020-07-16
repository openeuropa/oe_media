<?php

declare(strict_types = 1);

namespace Drupal\oe_media_iframe\Plugin\media\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaSourceBase;

/**
 * Iframe media source.
 *
 * @MediaSource(
 *   id = "oe_media_iframe",
 *   label = @Translation("Iframe"),
 *   description = @Translation("Use iframes as source for media entities."),
 *   allowed_field_types = {"string_long"},
 *   default_thumbnail_filename = "video.png"
 * )
 */
class Iframe extends MediaSourceBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'text_format' => 'oe_media_iframe',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $text_formats = [];
    /** @var \Drupal\filter\FilterFormatInterface $filter_format */
    foreach (filter_formats() as $filter_format) {
      $text_formats[$filter_format->get('format')] = $filter_format->get('name');
    }

    $form['text_format'] = [
      '#title' => $this->t('Text format'),
      '#type' => 'select',
      '#options' => $text_formats,
      '#default_value' => $this->getConfiguration()['text_format'],
      '#description' => $this->t('Pick the text format which could be used with an iframe input field.'),
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

}
