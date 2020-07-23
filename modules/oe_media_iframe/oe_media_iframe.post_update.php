<?php

/**
 * @file
 * Post update functions for OpenEuropa Media Iframe module.
 */

declare(strict_types = 1);

/**
 * Incorporate text format setting into Iframe media source.
 */
function oe_media_iframe_post_update_00001(): void {
  $format = \Drupal::entityTypeManager()->getStorage('filter_format')->create([
    'format' => 'oe_media_iframe',
    'name' => 'Iframe Media',
    'filters' => [
      'filter_html' => [
        'settings' => [
          'allowed_html' => '<iframe allow allowfullscreen allowpaymentrequest csp height importance loading name referrerpolicy sandbox src srcdoc width mozallowfullscreen webkitAllowFullScreen scrolling frameborder>',
        ],
        'status' => TRUE,
      ],
    ],
  ]);
  $format->save();

  $entity_form_storage = \Drupal::entityTypeManager()->getStorage('entity_form_display');
  $form_display = $entity_form_storage->load('media.video_iframe.default');
  $source_field = $form_display->getComponent('oe_media_iframe');
  if ($source_field) {
    $source_field['type'] = 'oe_media_iframe';
    $form_display->setComponent('oe_media_iframe', $source_field);
    $form_display->save();
    // Invalidate the cache of related config manually as workaround.
    \Drupal::cache('config')->delete('core.entity_form_display.media.video_iframe.default');
  }
  $video_iframe = \Drupal::entityTypeManager()->getStorage('media_type')->load('video_iframe');
  $settings = $video_iframe->getSource()->getConfiguration();
  $settings['text_format'] = 'oe_media_iframe';
  $video_iframe->set('source_configuration', $settings);
  $video_iframe->save();
}
