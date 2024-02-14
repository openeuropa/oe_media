<?php

declare(strict_types=1);

namespace Drupal\oe_media_iframe\Plugin\media\Source;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;

/**
 * Iframe media source.
 *
 * @MediaSource(
 *   id = "oe_media_iframe",
 *   label = @Translation("Iframe"),
 *   description = @Translation("Use iframes as source for media entities."),
 *   allowed_field_types = {"string_long"},
 *   default_thumbnail_filename = "video.png",
 *   thumbnail_alt_metadata_attribute = "thumbnail_alt_value"
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
  public function getMetadata(MediaInterface $media, $attribute_name) {
    switch ($attribute_name) {
      case 'thumbnail_uri':
        $thumbnail = $media->get('oe_media_iframe_thumbnail')->entity;
        if (!$thumbnail instanceof FileInterface) {
          return parent::getMetadata($media, $attribute_name);
        }
        return $thumbnail->getFileUri();

      case 'thumbnail_alt_value':
        return $media->get('oe_media_iframe_thumbnail')->alt ?? '';

      default:
        return parent::getMetadata($media, $attribute_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    $this->entityTypeManager->getStorage('media_type')->resetCache();
    // Create the thumbnail field at the same time with the source field.
    $fields = $this->entityFieldManager->getFieldStorageDefinitions('media');
    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    if (!isset($fields['oe_media_iframe_thumbnail'])) {
      $storage = $this->entityTypeManager
        ->getStorage('field_storage_config')
        ->create([
          'entity_type' => 'media',
          'field_name' => 'oe_media_iframe_thumbnail',
          'type' => 'image',
        ]);
      $storage->save();
    }
    else {
      $storage = $fields['oe_media_iframe_thumbnail'];
    }
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = $this->entityTypeManager
      ->getStorage('field_config')
      ->create([
        'field_storage' => $storage,
        'bundle' => $type->id(),
        'label' => 'Iframe thumbnail',
        'required' => FALSE,
        'translatable' => FALSE,
      ]);
    $field->save();
    if (!isset($fields['oe_media_iframe_title'])) {
      $storage = $this->entityTypeManager
        ->getStorage('field_storage_config')
        ->create([
          'entity_type' => 'media',
          'field_name' => 'oe_media_iframe_title',
          'type' => 'string',
        ]);
      $storage->save();
    }
    else {
      $storage = $fields['oe_media_iframe_title'];
    }
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = $this->entityTypeManager
      ->getStorage('field_config')
      ->create([
        'field_storage' => $storage,
        'bundle' => $type->id(),
        'label' => 'Iframe title',
        'description' => 'Providing an Iframe title value will replace the title value in the iframe html.',
        'required' => FALSE,
        'translatable' => FALSE,
      ]);
    $field->save();

    return parent::createSourceField($type);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareFormDisplay(MediaTypeInterface $type, EntityFormDisplayInterface $display) {
    parent::prepareFormDisplay($type, $display);
    // Place title field after the name field.
    $name_component = $display->getComponent('name');
    $title_field_weight = ($name_component && isset($name_component['weight'])) ? $name_component['weight'] + 1 : -40;
    $display->setComponent('oe_media_iframe_title', [
      'weight' => $title_field_weight,
    ]);
    // Place thumbnail field after the source field.
    $source_component = $display->getComponent($this->getSourceFieldDefinition($type)->getName());
    $thumbnail_weight = ($source_component && isset($source_component['weight'])) ? $source_component['weight'] + 1 : -50;
    $display->setComponent('oe_media_iframe_thumbnail', [
      'weight' => $thumbnail_weight,
    ]);
    $source_component['type'] = 'oe_media_iframe_textarea';
    $display->setComponent($this->getSourceFieldDefinition($type)->getName(), $source_component);
    $display->save();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    parent::prepareViewDisplay($type, $display);
    $source_component = $display->getComponent($this->getSourceFieldDefinition($type)->getName());
    $source_component['type'] = 'oe_media_iframe';
    $display->setComponent($this->getSourceFieldDefinition($type)->getName(), $source_component);
    $display->save();
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
      '#description' => $this->t('Pick the text format that will be used to render the iframe code.'),
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

}
