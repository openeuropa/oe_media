<?php

/**
 * @file
 * Post update functions for OpenEuropa Media Avportal module.
 */

declare(strict_types=1);

/**
 * Set access content permission to circabc_entity_browser.
 */
function oe_media_avportal_post_update_00001(array &$sandbox) {
  $storage = \Drupal::entityTypeManager()->getStorage('view');
  $view = $storage->load('av_portal_entity_browsers');

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
