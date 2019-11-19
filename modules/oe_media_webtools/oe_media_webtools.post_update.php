<?php

/**
 * @file
 * Post update functions for OpenEuropa Webtools Media module.
 */

declare(strict_types = 1);

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Remove 'Description' field from chart, map and tweeter feed media type.
 */
function oe_media_webtools_post_update_00001(): void {
  foreach (['webtools_chart', 'webtools_map', 'webtools_social_feed'] as $bundle) {
    $field = FieldConfig::load('media.' . $bundle . '.' . 'oe_media_webtools_description');
    if (!$field) {
      continue;
    }
    $field->delete();
  }
  if ($field = FieldStorageConfig::load('media.oe_media_webtools_description')) {
    $field->delete();
  }

}
