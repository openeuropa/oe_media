<?php

/**
 * @file
 * Post update functions for OpenEuropa Media module.
 */

declare(strict_types = 1);

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

function oe_media_post_update_change_document_media_source() {
  $document = \Drupal\media\Entity\MediaType::load('document');
  $document->set('source', 'file_with_thumbnail');
  $document->save();

  // @todo Flush caches
}
