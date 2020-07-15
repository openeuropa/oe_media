<?php

/**
 * @file
 * Post update functions for OpenEuropa Media Iframe module.
 */

declare(strict_types = 1);

use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Incorporate text format setting into Iframe media source.
 */
function oe_media_iframe_post_update_00001() {
  $format = \Drupal::entityTypeManager()->getStorage('filter_format')->create([
    'format' => 'oe_media_iframe',
    'name' => 'Iframe Media',
    'filters' => [
      'filter_html' => [
        'allowed_html' => '<iframe allow allowfullscreen allowpaymentrequest csp height importance loading name referrerpolicy sandbox src srcdoc width mozallowfullscreen webkitAllowFullScreen scrolling frameborder accesskey autocapitalize class contenteditable data-* dir draggable dropzone exportparts hidden id inputmode is itemid itemprop itemref itemscope itemtype lang part slot spellcheck style tabindex title translate>',
        'status' => TRUE,
      ],
    ],
  ]);
  $format->save();

  $form_display = EntityFormDisplay::load('media.video_iframe.default');
  $source_field = $form_display->getComponent('oe_media_iframe');
  if ($source_field) {
    $form_display['type'] = 'oe_media_iframe';
    $form_display->setComponent('oe_media_iframe', $source_field);
    $form_display->save();
  }
  $video_iframe = \Drupal::entityTypeManager()->getStorage('media_type')->load('video_iframe');
  $settings = $video_iframe->getSource()->getConfiguration();
  $settings['text_format'] = 'oe_media_iframe';
  $video_iframe->set('source_configuration', $settings);
  $video_iframe->save();
}
