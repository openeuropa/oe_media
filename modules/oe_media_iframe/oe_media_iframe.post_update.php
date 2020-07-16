<?php

/**
 * @file
 * Post update functions for OpenEuropa Media iframe module.
 */

declare(strict_types = 1);

/**
 * Add thumbnail field to video iframe media.
 */
function oe_media_iframe_post_update_thumbnail() {
  $entity_type_manager = \Drupal::entityTypeManager();
  $media_type_storage = $entity_type_manager->getStorage('media_type');
  $iframe_types = $media_type_storage->loadByProperties(['source' => 'oe_media_iframe']);
  foreach ($iframe_types as $type) {
    $type->getSource()->createSourceField($type);
    // Update default form display to include the thumbnail field.
    $form_display = $entity_type_manager->getStorage('entity_form_display')->load('media.' . $type->id() . '.default');
    $type->getSource()->prepareFormDisplay($type, $form_display);
  }
}
