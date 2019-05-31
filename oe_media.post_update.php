<?php

/**
 * @file
 * Post update functions for OpenEuropa Media module.
 */

declare(strict_types = 1);

use Drupal\Core\Config\FileStorage;

/**
 * Update allowed file extensions.
 */
function oe_media_post_update_allowed_file_extensions(): void {
  $storage = new FileStorage(drupal_get_path('module', 'oe_media') . '/config/updates/allowed_file_extensions');

  $config_files = [
    'field.field.media.document.oe_media_file',
  ];

  $config_manager = \Drupal::service('config.manager');
  $entity_manager = \Drupal::entityTypeManager();
  foreach ($config_files as $config_name) {
    $config_record = $storage->read($config_name);
    $entity_type = $config_manager->getEntityTypeIdByName($config_name);
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $config_entity_storage */
    $config_entity_storage = $entity_manager->getStorage($entity_type);
    $active_config_entity = $config_entity_storage->load($config_record['id']);
    $config = $config_entity_storage->updateFromStorageRecord($active_config_entity, $config_record);
    $config->save();
  }
}
