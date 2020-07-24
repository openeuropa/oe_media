<?php

/**
 * @file
 * Post update functions for OpenEuropa Media iframe module.
 */

declare(strict_types = 1);

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
