<?php

/**
 * @file
 * Post update functions for OpenEuropa Media Webtools module.
 */

declare(strict_types = 1);

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
    return 'The field description update for the following fields was skipped as it was changed: ' . implode(', ', $modified);
  }
}
