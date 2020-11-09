<?php

/**
 * @file
 * Post update functions for OpenEuropa Media module.
 */

declare(strict_types = 1);

use Drupal\Core\Config\FileStorage;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Update allowed file extensions.
 */
function oe_media_post_update_allowed_file_extensions(): void {
  $field = FieldConfig::load('media.document.oe_media_file');
  $field->setSetting('file_extensions', 'txt text md readme info doc dot docx dotx docm dotm xls xlt xla xlsx xltx xlsm xltm xlam xlsb ppt pot pps ppa pptx potx ppsx ppam pptm potm ppsm pdf ods odt odf');
  $field->save();
}

/**
 * Move the document files to the private file system.
 */
function oe_media_post_update_document_private_files(): void {
  $field = FieldStorageConfig::load('media.oe_media_file');
  if ($field instanceof FieldStorageConfigInterface && $field->getSetting('uri_scheme') !== 'private') {
    $field->setSetting('uri_scheme', 'private');
    $field->save();
  }
}

/**
 * Add new fields to document media bundle to allow remote files.
 */
function oe_media_post_update_00001_remote_file(array &$sandbox) {
  // Enable file_link module.
  $module_installer = \Drupal::service('module_installer');
  $module_installer->install(['options', 'file_link']);

  \Drupal::service('plugin.manager.field.field_type')->clearCachedDefinitions();

  // Create the new fields.
  $entity_type_manager = \Drupal::entityTypeManager();
  $config_manager = \Drupal::service('config.manager');
  $storage = new FileStorage(drupal_get_path('module', 'oe_media') . '/config/post_updates/00001_remote_file');
  $field_configs = [
    'field.storage.media.oe_media_file_type',
    'field.storage.media.oe_media_remote_file',
    'field.field.media.document.oe_media_file_type',
    'field.field.media.document.oe_media_remote_file',
  ];
  foreach ($field_configs as $config) {
    $config_record = $storage->read($config);
    $entity_type = $config_manager->getEntityTypeIdByName($config);
    $entity_storage = $entity_type_manager->getStorage($entity_type);
    if (!$entity_storage->load($config_record['id'])) {
      $entity = $entity_storage->createFromStorageRecord($config_record);
      $entity->save();
    }
  }

  // Set the file field optional.
  $field_config = FieldConfig::load('media.document.oe_media_file');
  $field_config->setRequired(FALSE);
  $field_config->save();

  $entity_type_manager->clearCachedDefinitions();
}

/**
 * Set all the existing Document media entities to local files.
 */
function oe_media_post_update_00002_existing_local_documents(array &$sandbox) {
  $media_storage = \Drupal::entityTypeManager()->getStorage('media');
  if (!isset($sandbox['total'])) {
    $query = $media_storage->getQuery();
    $query->condition('bundle', 'document');
    $document_ids = $query->execute();
    if (empty($document_ids)) {
      // We don't have to do anything if there are no document media entities.
      $sandbox['#finished'] = 1;
      return t('There are no Document media entities to update.');
    }

    $sandbox['current'] = 0;
    $sandbox['documents_per_batch'] = 1;
    $sandbox['document_ids'] = $document_ids;
    $sandbox['total'] = count($sandbox['document_ids']);
  }

  $ids = array_slice($sandbox['document_ids'], $sandbox['current'], $sandbox['documents_per_batch']);
  $documents_to_update = $media_storage->loadMultiple($ids);
  foreach ($documents_to_update as $document) {
    $document->set('oe_media_file_type', 'local');
    $document->save();
    $sandbox['current']++;
  }

  $sandbox['#finished'] = empty($sandbox['total']) ? 1 : ($sandbox['current'] / $sandbox['total']);

  if ($sandbox['#finished'] === 1) {
    return t('All the existing document media entities have been set to local files.');
  }
}
