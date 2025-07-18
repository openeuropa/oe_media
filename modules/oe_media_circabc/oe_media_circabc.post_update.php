<?php

/**
 * @file
 * Post update functions for OpenEuropa Media CircaBC module.
 */

declare(strict_types=1);

use Drupal\Core\Config\FileStorage;

/**
 * Add content owner filter to the CircaBC browser.
 */
function oe_media_circabc_post_update_00001() {
  $storage = new FileStorage(\Drupal::service('extension.list.module')->getPath('oe_media_circabc') . '/config/post_updates/00001_add_content_owner');
  _oe_media_import_config_from_file('views.view.circabc_entity_browser', $storage);
}
