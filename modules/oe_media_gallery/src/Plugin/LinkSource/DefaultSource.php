<?php

declare(strict_types = 1);

namespace Drupal\oe_media_gallery\Plugin\LinkSource;

/**
 * Default link source plugin for the Gallery.
 *
 * @LinkSource(
 *   id = "oe_media_gallery_default",
 *   label = @Translation("Media"),
 *   description = @Translation("Source plugin that handles media."),
 *   bundles = { "gallery" }
 * )
 */
class DefaultSource extends GalleryLinkSourceBase {}
