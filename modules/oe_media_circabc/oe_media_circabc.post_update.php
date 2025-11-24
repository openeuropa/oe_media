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

/**
 * Set access content permission to circabc_entity_browser.
 */
function oe_media_circabc_post_update_00002(array &$sandbox) {

  $storage = \Drupal::entityTypeManager()->getStorage('view');
  $view = $storage->load('circabc_entity_browser');

  if ($view) {
    $displays = $view->get('display');

    if (isset($displays['default']['display_options']['access']['type'])
      && $displays['default']['display_options']['access']['type'] === 'none') {
      $displays['default']['display_options']['access'] = [
        'type' => 'perm',
        'options' => [
          'perm' => 'access content',
        ],
      ];
      $view->set('display', $displays);
      $view->save();
    }
  }
}
