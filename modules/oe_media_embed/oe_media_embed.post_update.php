<?php

/**
 * @file
 * Post update functions for OpenEuropa Media Embed module.
 */

declare(strict_types=1);

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\embed\Entity\EmbedButton;
use Drupal\filter\Entity\FilterFormat;

/**
 * Make view modes that are already available embeddable by default.
 */
function oe_media_embed_post_update_00001(): void {
  $available_view_display_ids = \Drupal::entityQuery('entity_view_display')
    ->condition('targetEntityType', 'media')
    ->condition('status', TRUE)
    ->accessCheck(FALSE)
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
      if ($filter['provider'] === 'oe_media_embed' && $filter['id'] === 'media_embed') {
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

/**
 * Update the embed buttons to use the new generic embed type.
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
function oe_media_embed_post_update_00003(): void {
  // Install oe_oembed.
  \Drupal::service('module_installer')->install(['oe_oembed']);

  // Update all existing buttons.
  /** @var \Drupal\embed\EmbedButtonInterface[] $buttons */
  $buttons = EmbedButton::loadMultiple();
  foreach ($buttons as $button) {
    if ($button->getTypeId() !== 'embed_media') {
      continue;
    }

    $button->set('type_id', 'oe_oembed_entities');
    $settings = $button->getTypeSettings();
    $media_types = $settings['media_types'];
    unset($settings['media_types']);
    $settings['entity_type'] = 'media';
    $settings['bundles'] = array_filter($media_types);
    $button->set('type_settings', $settings);

    // Set the default icon if the button doesn't already use a specific one.
    $icon = $button->get('icon');
    if (!$icon) {
      $path = \Drupal::service('extension.list.module')->getPath('oe_media_embed') . '/embed.png';
      $icon = EmbedButton::convertImageToEncodedData($path);
      $button->set('icon', $icon);
    }

    $button->save();
  }

  // Update all the entity view display third party settings.
  /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface[] $entity_view_displays */
  $entity_view_displays = EntityViewDisplay::loadMultiple();
  foreach ($entity_view_displays as $entity_view_display) {
    $setting = $entity_view_display->getThirdPartySetting('oe_media_embed', 'embeddable', NULL);
    if (is_null($setting)) {
      // We don't need to update anything if there is no setting.
      continue;
    }
    $entity_view_display->setThirdPartySetting('oe_oembed', 'embeddable', $setting);
    $entity_view_display->unsetThirdPartySetting('oe_media_embed', 'embeddable');
    $entity_view_display->save();
  }

  // Update the text format filters.
  $available_formats = FilterFormat::loadMultiple();
  /** @var \Drupal\filter\FilterFormatInterface $available_format */
  foreach ($available_formats as $available_format) {
    $filters = $available_format->get('filters');
    foreach ($filters as $filter_id => $filter) {
      if ($filter['provider'] === 'oe_media_embed' && $filter['id'] === 'oe_media_embed') {
        $filter['id'] = 'oe_oembed_filter';
        $filter['provider'] = 'oe_oembed';
        $filters['oe_oembed_filter'] = $filter;
        continue;
      }
    }
    if (isset($filters['oe_media_embed'])) {
      unset($filters['oe_media_embed']);
      // Use the config API instead of the entity API because Drupal will
      // crash due to missing filter before saving the config entity.
      $config = \Drupal::configFactory()->getEditable('filter.format.' . $available_format->id());
      $config->set('filters', $filters);
      $config->save();
    }
  }
}

/**
 * Remove old oe_media_embed.settings.
 */
function oe_media_embed_post_update_00004(): void {
  \Drupal::configFactory()->getEditable('oe_media_embed.settings')->delete();
}
