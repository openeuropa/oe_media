<?php

/**
 * @file
 * Post update functions for OpenEuropa Media Webtools module.
 */

declare(strict_types = 1);

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
  $file_storage = new FileStorage(drupal_get_path('module', 'oe_media_webtools') . '/config/post_updates/00002_install_op_publication_list');
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
