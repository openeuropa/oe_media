<?php

/**
 * @file
 * Post update functions for OpenEuropa Media iframe module.
 */

declare(strict_types = 1);

use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Add thumbnail field to media types using iframe source.
 */
function oe_media_iframe_post_update_00001(): void {
  $entity_type_manager = \Drupal::entityTypeManager();
  $media_type_storage = $entity_type_manager->getStorage('media_type');
  $iframe_types = $media_type_storage->loadByProperties(['source' => 'oe_media_iframe']);

  $fields = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('media');
  if (!isset($fields['oe_media_iframe_thumbnail'])) {
    $storage = $entity_type_manager
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

  foreach ($iframe_types as $type) {
    $field = $entity_type_manager
      ->getStorage('field_config')
      ->create([
        'field_storage' => $storage,
        'bundle' => $type->id(),
        'label' => 'Iframe thumbnail',
        'required' => FALSE,
        'translatable' => FALSE,
      ]);
    $field->save();

    // Update default form display to include the thumbnail after source.
    $form_display = $entity_type_manager->getStorage('entity_form_display')->load('media.' . $type->id() . '.default');
    $source_field_name = $type->getSource()->getSourceFieldDefinition($type)->getName();
    $source_component = $form_display->getComponent($source_field_name);
    $thumbnail_weight = ($source_component && isset($source_component['weight'])) ? $source_component['weight'] + 1 : -50;
    $form_display->setComponent('oe_media_iframe_thumbnail', [
      'weight' => $thumbnail_weight,
    ])->save();
  }
}

/**
 * Incorporate text format setting into Iframe media source.
 */
function oe_media_iframe_post_update_00002() {
  $format = \Drupal::entityTypeManager()->getStorage('filter_format')->create([
    'format' => 'oe_media_iframe',
    'name' => 'Iframe Media',
    'filters' => [
      'filter_html' => [
        'allowed_html' => '<iframe allow allowfullscreen allowpaymentrequest csp height importance loading name referrerpolicy sandbox src srcdoc width mozallowfullscreen webkitAllowFullScreen scrolling frameborder accesskey autocapitalize class contenteditable data-* dir draggable dropzone exportparts hidden id inputmode is itemid itemprop itemref itemscope itemtype lang part slot spellcheck style tabindex title translate>',
        'status' => TRUE,
      ],
    ],
  ]);
  $format->save();

  $form_display = EntityFormDisplay::load('media.video_iframe.default');
  $source_field = $form_display->getComponent('oe_media_iframe');
  if ($source_field) {
    $form_display['type'] = 'oe_media_iframe';
    $form_display->setComponent('oe_media_iframe', $source_field);
    $form_display->save();
  }
  $video_iframe = \Drupal::entityTypeManager()->getStorage('media_type')->load('video_iframe');
  $settings = $video_iframe->getSource()->getConfiguration();
  $settings['text_format'] = 'oe_media_iframe';
  $video_iframe->set('source_configuration', $settings);
  $video_iframe->save();
}
