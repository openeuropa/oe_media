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
function oe_media_webtools_post_update_00001(): void {
  $fields = [
    'media.webtools_chart.oe_media_webtools',
    'media.webtools_map.oe_media_webtools',
    'media.webtools_social_feed.oe_media_webtools',
  ];
  foreach ($fields as $field) {
    $field_config = FieldConfig::load($field);
    $field_config->setDescription('Enter the snippet without the script tag. Snippets can be generated in <a href="https://europa.eu/webtools/mgmt/wizard/" target="_blank">Webtools wizard</a>.');
    $field_config->save();
  }
}
