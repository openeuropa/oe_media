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
function oe_media_post_update_00001(array &$sandbox) {
  // Enable file_link module.
  $module_installer = \Drupal::service('module_installer');
  $module_installer->install(['options', 'file_link']);

  \Drupal::service('plugin.manager.field.field_type')->clearCachedDefinitions();
  \Drupal::service('kernel')->invalidateContainer();

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
function oe_media_post_update_00002(array &$sandbox) {
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
    $sandbox['documents_per_batch'] = 5;
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

/**
 * Update document media translations to set a file type.
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
function oe_media_post_update_00003(&$sandbox) {
  // If the file field is translatable, we need to make
  // the field type and remote file fields translatable too.
  $entity_type_manager = \Drupal::entityTypeManager();
  $field_config_storage = $entity_type_manager->getStorage('field_config');
  /** @var \Drupal\Core\Field\FieldConfigInterface $field_instance */
  $field_instance = $field_config_storage->load('media.document.oe_media_file');
  if ($field_instance->isTranslatable()) {
    $field_ids = [
      'media.document.oe_media_file_type',
      'media.document.oe_media_remote_file',
    ];
    foreach ($field_ids as $field_id) {
      $field_instance = $field_config_storage->load($field_id);
      $field_instance->setTranslatable(TRUE);
      $field_instance->save();
    }
  }

  $field_instance->setTranslatable(TRUE);
  $field_instance->save();

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
    $sandbox['documents_per_batch'] = 5;
    $sandbox['document_ids'] = $document_ids;
    $sandbox['total'] = count($sandbox['document_ids']);
    $sandbox['updated'] = 0;
  }

  $ids = array_slice($sandbox['document_ids'], $sandbox['current'], $sandbox['documents_per_batch']);
  $documents_to_update = $media_storage->loadMultiple($ids);

  /** @var \Drupal\media\MediaInterface $media */
  foreach ($documents_to_update as $media) {
    $changed = FALSE;
    if (!$media->isTranslatable()) {
      continue;
    }
    foreach ($media->getTranslationLanguages(FALSE) as $langcode => $language) {
      $translation = $media->getTranslation($langcode);
      if (!$translation->get('oe_media_file_type')->isEmpty()) {
        // If we already have a value, we move on.
        continue;
      }

      // Otherwise, we need to determine what kind of document it is.
      if (!$translation->get('oe_media_remote_file')->isEmpty()) {
        // If we have a remote file, we mark it as remote.
        $translation->set('oe_media_file_type', 'remote');
        $changed = TRUE;
        continue;
      }

      // Otherwise, we default to local.
      $translation->set('oe_media_file_type', 'local');
      $changed = TRUE;
    }

    if ($changed) {
      $media->save();
      $sandbox['updated']++;
    }

    $sandbox['current']++;
  }

  $sandbox['#finished'] = empty($sandbox['total']) ? 1 : ($sandbox['current'] / $sandbox['total']);

  if ($sandbox['#finished'] === 1) {
    return t('A total number of @count document media entities have had their translations updated.', ['@count' => $sandbox['updated']]);
  }
}
