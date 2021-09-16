<?php

declare(strict_types = 1);

namespace Drupal\oe_media_embed\Plugin\Filter;

use Drupal\embed\DomHelperTrait;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to convert PURL into internal urls/aliases.
 *
 * This filter is no longer used, instead the one provided by oe_oembed should
 * be used. We keep this class and plugin here in order to prevent sites
 * that upgrade to have any issues upgrading due to missing plugin used in
 * configured plugin collections.
 *
 * @deprecated
 *
 * @Filter(
 *   id = "oe_media_embed",
 *   title = @Translation("Embeds media entities using the oEmbed format"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterMediaEmbed extends FilterBase {

  use DomHelperTrait;

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return $text;
  }

}
