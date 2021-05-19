<?php

/**
 * @file
 * Post update functions for OpenEuropa Media module.
 */

declare(strict_types = 1);

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\filter\Entity\FilterFormat;

/**
 * Make view modes that are already available embeddable by default.
 */
function oe_media_embed_post_update_00001(): void {
  $available_view_display_ids = \Drupal::entityQuery('entity_view_display')
    ->condition('targetEntityType', 'media')
    ->condition('status', TRUE)
    ->execute();
  $available_view_displays = EntityViewDisplay::loadMultiple($available_view_display_ids);
  /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
  foreach ($available_view_displays as $view_display) {
    if ($view_display->getThirdPartySetting('oe_media_embed', 'embeddable') === NULL) {
      $view_display->setThirdPartySetting('oe_media_embed', 'embeddable', TRUE);
      $view_display->save();
    }
  }
}

/**
 * Update media embed filter plugin id.
 */
function oe_media_embed_post_update_00002(): void {
  $available_formats = FilterFormat::loadMultiple();
  /** @var \Drupal\filter\FilterFormatInterface $available_format */
  foreach ($available_formats as $available_format) {
    $filters = $available_format->get('filters');
    foreach ($filters as $filter_id => $filter) {
      if ($filter['provider'] == 'oe_media_embed' && $filter['id'] === 'media_embed') {
        $filter['id'] = 'oe_media_embed';
        $filters['oe_media_embed'] = $filter;
        continue;
      }
    }
    if (isset($filters['oe_media_embed'])) {
      unset($filters['media_embed']);
      $available_format->set('filters', $filters);
      $available_format->save();
    }
  }
}
