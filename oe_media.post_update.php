<?php

/**
 * @file
 * Post update functions for OpenEuropa Media module.
 */

declare(strict_types = 1);

use Drupal\field\Entity\FieldConfig;

/**
 * Update allowed file extensions.
 */
function oe_media_post_update_allowed_file_extensions(): void {
  $field = FieldConfig::load('media.document.oe_media_file');
  $field->setSetting('file_extensions', 'txt text md readme info doc dot docx dotx docm dotm xls xlt xla xlsx xltx xlsm xltm xlam xlsb ppt pot pps ppa pptx potx ppsx ppam pptm potm ppsm pdf ods odt odf');
  $field->save();
}
