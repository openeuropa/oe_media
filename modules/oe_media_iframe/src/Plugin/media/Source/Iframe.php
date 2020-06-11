<?php

declare(strict_types = 1);

namespace Drupal\oe_media_iframe\Plugin\media\Source;

use Drupal\media\MediaSourceBase;

/**
 * Iframe media source.
 *
 * @MediaSource(
 *   id = "oe_media_iframe",
 *   label = @Translation("Iframe"),
 *   description = @Translation("Use iframes as source for media entities."),
 *   allowed_field_types = {"string_long"}
 * )
 */
class Iframe extends MediaSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [];
  }

}
