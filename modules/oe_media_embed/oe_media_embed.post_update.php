<?php

/**
 * @file
 * Post update functions for OpenEuropa Media module.
 */

declare(strict_types = 1);

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;

/**
 * Enable default embed view modes.
 */
function oe_media_embed_post_update_00001(): void {
  $storage = new FileStorage(drupal_get_path('module', 'oe_media_embed') . '/config/post_updates/enable_embed_view_modes');
  $view_mode_values = $storage->read('core.entity_view_mode.media.oe_embed');
  if (!EntityViewMode::load($view_mode_values['id'])) {
    $view_mode = EntityViewMode::create($view_mode_values);
    $view_mode->save();
  }

  $display_modes = [
    'core.entity_view_display.media.document.oe_embed',
    'core.entity_view_display.media.image.oe_embed',
    'core.entity_view_display.media.remote_video.oe_embed',
  ];
  if (\Drupal::moduleHandler()->moduleExists('oe_media_avportal')) {
    $display_modes = array_merge($display_modes, [
      'core.entity_view_display.media.av_portal_photo.oe_embed',
      'core.entity_view_display.media.av_portal_video.oe_embed',
    ]);
  }
  if (\Drupal::moduleHandler()->moduleExists('oe_media_webtools')) {
    $display_modes = array_merge($display_modes, [
      'core.entity_view_display.media.webtools_chart.oe_embed',
      'core.entity_view_display.media.webtools_map.oe_embed',
      'core.entity_view_display.media.webtools_social_feed.oe_embed',
    ]);
  }
  foreach ($display_modes as $display_id) {
    $display_values = $storage->read($display_id);
    if (!EntityViewDisplay::load($display_values['id'])) {
      $display_mode = EntityViewDisplay::create($display_values);
      $display_mode->save();
    }
  }
}
