<?php

/**
 * @file
 * Post update functions for OpenEuropa Media Webtools module.
 */

declare(strict_types=1);

use Drupal\Core\Config\FileStorage;
use Drupal\field\Entity\FieldConfig;

/**
 * Update webtools media fields description to open the wizard in new tab.
 */
function oe_media_webtools_post_update_00001() {
  $original_description = 'Enter the snippet without the script tag. Snippets can be generated in <a href="https://europa.eu/webtools/mgmt/wizard/">Webtools wizzard</a>.';
  $new_description = 'Enter the snippet without the script tag. Snippets can be generated in <a href="https://europa.eu/webtools/mgmt/wizard/" target="_blank">Webtools wizard</a>.';
  $fields = [
    'media.webtools_chart.oe_media_webtools',
    'media.webtools_map.oe_media_webtools',
    'media.webtools_social_feed.oe_media_webtools',
  ];
  $modified = [];

  foreach ($fields as $field) {
    $field_config = FieldConfig::load($field);
    // If the description has been customised by users, we don’t change it.
    if ($original_description !== $field_config->get('description')) {
      $modified[] = $field;
      continue;
    }
    $field_config->setDescription($new_description);
    $field_config->save();
  }

  if (!empty($modified)) {
    return sprintf('The field description update for the following fields was skipped as their description was changed: %s.', implode(', ', $modified));
  }
}

/**
 * Install OP Publication List media type.
 */
function oe_media_webtools_post_update_00002() {
  $file_storage = new FileStorage(\Drupal::service('extension.list.module')->getPath('oe_media_webtools') . '/config/post_updates/00002_install_op_publication_list');
  $config_names = [
    'media.type.webtools_op_publication_list',
    'field.field.media.webtools_op_publication_list.oe_media_webtools',
    'core.entity_form_display.media.webtools_op_publication_list.default',
    'core.entity_view_display.media.webtools_op_publication_list.default',
  ];
  foreach ($config_names as $name) {
    _oe_media_import_config_from_file($name, $file_storage, TRUE, FALSE);
  }
}

/**
 * Install Webtools Generic media type.
 */
function oe_media_webtools_post_update_00003() {
  $storage = new FileStorage(\Drupal::service('extension.list.module')->getPath('oe_media_webtools') . '/config/post_updates/00003_install_webtools_generic');
  $config_names = [
    'media.type.webtools_generic',
    'field.field.media.webtools_generic.oe_media_webtools',
    'core.entity_form_display.media.webtools_generic.default',
    'core.entity_view_display.media.webtools_generic.default',
  ];
  foreach ($config_names as $name) {
    _oe_media_import_config_from_file($name, $storage, TRUE, FALSE);
  }
}

/**
 * Add Webtools countdown media type.
 */
function oe_media_webtools_post_update_00004() {
  $storage = new FileStorage(\Drupal::service('extension.list.module')->getPath('oe_media_webtools') . '/config/post_updates/00004_webtools_countdown');
  $config_names = [
    'media.type.webtools_countdown',
    'field.field.media.webtools_countdown.oe_media_webtools',
    'core.entity_form_display.media.webtools_countdown.default',
    'core.entity_view_display.media.webtools_countdown.default',
  ];
  foreach ($config_names as $name) {
    _oe_media_import_config_from_file($name, $storage);
  }
}

/**
 * Update Webtools media fields description to include the WCLOUD wizard link.
 */
function oe_media_webtools_post_update_00005() {
  $original_description = 'Enter the snippet without the script tag. Snippets can be generated in <a href="https://europa.eu/webtools/mgmt/wizard/" target="_blank">Webtools wizard</a>.';
  $new_description = 'Enter the snippet without the script tag. Snippets can be generated in <a href="https://europa.eu/webtools/tools/#/wizards" target="_blank">Webtools wizard</a> or in the newer <a href="https://europa.eu/webtools/tools/#/wcloud/" target="_blank">WCLOUD wizard</a>.';
  $fields = [
    'media.webtools_chart.oe_media_webtools',
    'media.webtools_countdown.oe_media_webtools',
    'media.webtools_generic.oe_media_webtools',
    'media.webtools_map.oe_media_webtools',
    'media.webtools_social_feed.oe_media_webtools',
  ];
  $modified = [];

  foreach ($fields as $field) {
    $field_config = FieldConfig::load($field);
    // If the field doesn't exist anymore, skip it.
    if (!$field_config) {
      continue;
    }
    // If the description has been customised by users, we don’t change it.
    if ($original_description !== $field_config->get('description')) {
      $modified[] = $field;
      continue;
    }
    $field_config->setDescription($new_description);
    $field_config->save();
  }

  if (!empty($modified)) {
    return sprintf('The field description update for the following fields was skipped as their description was changed: %s.', implode(', ', $modified));
  }
}

/**
 * Update Webtools media fields description to use the cloud link.
 */
function oe_media_webtools_post_update_00006() {
  $original_description = 'Enter the snippet without the script tag. Snippets can be generated in <a href="https://europa.eu/webtools/tools/#/wizards" target="_blank">Webtools wizard</a> or in the newer <a href="https://europa.eu/webtools/tools/#/wcloud/" target="_blank">WCLOUD wizard</a>.';
  $new_description = 'Enter the snippet without the script tag. Snippets can be generated in <a href="https://webtools.europa.eu/tools/#/wizards" target="_blank">Webtools wizard</a> or in the newer <a href="https://webtools.europa.eu/tools/#/wcloud/" target="_blank">WCLOUD wizard</a>.';
  $fields = [
    'media.webtools_chart.oe_media_webtools',
    'media.webtools_countdown.oe_media_webtools',
    'media.webtools_generic.oe_media_webtools',
    'media.webtools_map.oe_media_webtools',
    'media.webtools_social_feed.oe_media_webtools',
  ];
  $modified = [];

  foreach ($fields as $field) {
    $field_config = FieldConfig::load($field);
    // If the field doesn't exist anymore, skip it.
    if (!$field_config) {
      continue;
    }
    // If the description has been customised by users, we don’t change it.
    if ($original_description !== $field_config->get('description')) {
      $modified[] = $field;
      continue;
    }
    $field_config->setDescription($new_description);
    $field_config->save();
  }

  if (!empty($modified)) {
    return sprintf('The field description update for the following fields was skipped as their description was changed: %s.', implode(', ', $modified));
  }
}
