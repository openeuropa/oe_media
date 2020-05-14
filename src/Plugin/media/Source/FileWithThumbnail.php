<?php

declare(strict_types = 1);

namespace Drupal\oe_media\Plugin\media\Source;

use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\Plugin\media\Source\File;

/**
 * Class FileWithThumbnail
 *
 * @MediaSource(
 *   id = "file_with_thumbnail",
 *   label = @Translation("File with thumbnail"),
 *   description = @Translation("Use local files for reusable media."),
 *   allowed_field_types = {"file"},
 *   default_thumbnail_filename = "generic.png",
 *   thumbnail_alt_metadata_attribute = "thumbnail_alt_value"
 * )
 */
class FileWithThumbnail extends File {

  public function getMetadata(MediaInterface $media, $attribute_name) {
    // At first iteration this has the value!
    $a = $media->get('thumbnail')->alt;
    switch ($attribute_name) {
      case 'thumbnail_uri':
        $thumbnail = $media->get('thumbnail')->entity;
        if (!$thumbnail instanceof FileInterface) {
          return parent::getMetadata($media, $attribute_name);
        }
        return $thumbnail->getFileUri();
        break;

      case 'thumbnail_alt_value':
        return $media->get('thumbnail')->alt ?? '';
        break;

      default:
        return parent::getMetadata($media, $attribute_name);
    }
  }


}
