<?php

declare(strict_types = 1);

namespace Drupal\oe_media_js_asset\Plugin\media\Source;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;

/**
 * JavaScript media source.
 *
 * @MediaSource(
 *   id = "javascript_asset",
 *   label = @Translation("JavaScript asset"),
 *   description = @Translation("Use JavaScript asset url as source for media entities."),
 *   allowed_field_types = {"oe_media_js_asset_url"}
 * )
 */
class JavaScriptAsset extends MediaSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    $display->setComponent($this->getSourceFieldDefinition($type)->getName(), [
      'type' => 'oe_media_js_asset_url',
    ]);
  }

}
