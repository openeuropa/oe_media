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

/**
 * Incorporate text format setting into Iframe media source.
 */
function oe_media_iframe_post_update_00002(): void {
  $format = \Drupal::entityTypeManager()->getStorage('filter_format')->load('oe_media_iframe');
  if (!$format) {
    $format = \Drupal::entityTypeManager()->getStorage('filter_format')->create([
      'format' => 'oe_media_iframe',
      'name' => 'Iframe Media',
      'filters' => [
        'filter_html' => [
          'settings' => [
            'allowed_html' => '<iframe allowfullscreen height importance loading name referrerpolicy sandbox src width mozallowfullscreen webkitAllowFullScreen scrolling frameborder>',
          ],
          'status' => TRUE,
        ],
      ],
    ]);
    $format->save();
  }

  $entity_form_storage = \Drupal::entityTypeManager()->getStorage('entity_form_display');
  $form_display = $entity_form_storage->load('media.video_iframe.default');
  $source_field = $form_display->getComponent('oe_media_iframe');
  if ($source_field) {
    $source_field['type'] = 'oe_media_iframe_textarea';
    $form_display->setComponent('oe_media_iframe', $source_field);
    $form_display->save();
    // We have to remove cache record directly because we can't invalidate
    // this cache record by tags ('cache_config' table have empty 'tags' field).
    // \Drupal::cache('config')->invalidate() do not work either because core,
    // for some reason, does not check the validity of the cache record.
    \Drupal::cache('config')->delete('core.entity_form_display.media.video_iframe.default');
  }
  $media_type_storage = \Drupal::entityTypeManager()->getStorage('media_type');
  $iframe_types = $media_type_storage->loadByProperties(['source' => 'oe_media_iframe']);
  foreach ($iframe_types as $iframe_media_type) {
    $settings = $iframe_media_type->getSource()->getConfiguration();
    $settings['text_format'] = 'oe_media_iframe';
    $iframe_media_type->set('source_configuration', $settings);
    $iframe_media_type->save();
  }

}
