<?php

/**
 * @file
 * Post update functions for OpenEuropa Media iframe module.
 */

declare(strict_types = 1);

use Drupal\Core\Config\FileStorage;
use Drupal\Component\Utility\Crypt;
use Drupal\field\Entity\FieldConfig;
use Drupal\filter\Entity\FilterFormat;

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
            'allowed_html' => '<iframe allowfullscreen height importance loading referrerpolicy sandbox src width mozallowfullscreen webkitAllowFullScreen scrolling frameborder>',
          ],
          'status' => TRUE,
          'weight' => -10,
        ],
        'filter_iframe_tag' => [
          'status' => TRUE,
          'weight' => 0,
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

/**
 * Make iframe ratio field storage translatable.
 */
function oe_media_iframe_post_update_00003(): void {
  $field = \Drupal::service('entity_type.manager')->getStorage('field_storage_config')->load('media.oe_media_iframe_ratio');
  $field->setTranslatable(TRUE)->save();
}

/**
 * Create Iframe media.
 */
function oe_media_iframe_post_update_00004(): void {
  $file_storage = new FileStorage(drupal_get_path('module', 'oe_media_iframe') . '/config/post_updates/00004_create_media_iframe');
  $config_data = $file_storage->read('media.type.iframe');

  // Create Iframe media if it isn't exist.
  $media_type_storage = \Drupal::entityTypeManager()->getStorage('media_type');
  $media_type = $media_type_storage->load($config_data['id']);
  if (!$media_type) {
    $config_data['_core']['default_config_hash'] = Crypt::hashBase64(serialize($config_data));
    $media_type_storage->create($config_data)->save();
  }
}

/**
 * Create Iframe media fields.
 */
function oe_media_iframe_post_update_00005(): void {
  $file_storage = new FileStorage(drupal_get_path('module', 'oe_media_iframe') . '/config/post_updates/00005_create_fields');

  $configs = [
    'field.field.media.iframe.oe_media_iframe',
    'field.field.media.iframe.oe_media_iframe_ratio',
    'field.field.media.iframe.oe_media_iframe_thumbnail',
  ];
  $config_storage = \Drupal::entityTypeManager()->getStorage('field_config');
  // Create fields if they do not exist.
  foreach ($configs as $config) {
    $config_data = $file_storage->read($config);
    if (!$config_storage->load($config_data['id'])) {
      $config_data['_core']['default_config_hash'] = Crypt::hashBase64(serialize($config_data));
      $config_storage->create($config_data)->save();
    }
  }
}

/**
 * Create form and display views for Iframe media.
 */
function oe_media_iframe_post_update_00006(): void {
  $file_storage = new FileStorage(drupal_get_path('module', 'oe_media_iframe') . '/config/post_updates/00006_create_displays');

  // Form display configuration to create.
  $form_display_values = $file_storage->read('core.entity_form_display.media.iframe.default');
  $entity_form_display_storage = \Drupal::entityTypeManager()->getStorage('entity_form_display');
  $form_display = $entity_form_display_storage->load($form_display_values['id']);
  if (!$form_display) {
    $form_display_values['_core']['default_config_hash'] = Crypt::hashBase64(serialize($form_display_values));
    $entity_form_display_storage->create($form_display_values)->save();
  }

  // View display configuration to create.
  $view_display_values = $file_storage->read('core.entity_view_display.media.iframe.default');
  $entity_view_display_storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
  $view_display = $entity_view_display_storage->load($view_display_values['id']);
  if (!$view_display) {
    $view_display_values['_core']['default_config_hash'] = Crypt::hashBase64(serialize($view_display_values));
    $entity_view_display_storage->create($view_display_values)->save();
  }
}

/**
 * Add iframe title field to media types using iframe source.
 */
function oe_media_iframe_post_update_00007(): void {
  $entity_type_manager = \Drupal::entityTypeManager();
  $media_type_storage = $entity_type_manager->getStorage('media_type');
  $iframe_types = $media_type_storage->loadByProperties(['source' => 'oe_media_iframe']);

  $fields = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('media');
  if (!isset($fields['oe_media_iframe_title'])) {
    $storage = $entity_type_manager
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

  foreach ($iframe_types as $type) {
    $id = $type->id();
    $field_config = FieldConfig::load("media.$id.oe_media_iframe_title");
    if ($field_config) {
      // If the current media type already has the field, we skip it.
      continue;
    }
    $field = $entity_type_manager
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

    // Update default form display to include the iframe title after name.
    $form_display = $entity_type_manager->getStorage('entity_form_display')->load('media.' . $type->id() . '.default');
    $name_component = $form_display->getComponent('name');
    $title_field_weight = ($name_component && isset($name_component['weight'])) ? $name_component['weight'] + 1 : -40;
    $form_display->setComponent('oe_media_iframe_title', [
      'weight' => $title_field_weight,
    ])->save();
  }

  // Allow title attribute for oe_media_iframe filter.
  $format = FilterFormat::load('oe_media_iframe');
  $filters = $format->get('filters');
  if (!isset($filters['filter_html']) || str_contains($filters['filter_html']['settings']['allowed_html'], 'title')) {
    return;
  }
  $filters['filter_html']['settings']['allowed_html'] = str_replace('>', ' title>', $filters['filter_html']['settings']['allowed_html']);
  $format->set('filters', $filters);
  $format->save();
}
