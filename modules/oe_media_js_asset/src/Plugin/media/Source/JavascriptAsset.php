<?php

declare(strict_types = 1);

namespace Drupal\oe_media_js_asset\Plugin\media\Source;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;

/**
 * Javascript media source.
 *
 * @MediaSource(
 *   id = "javascript_asset",
 *   label = @Translation("Javascript asset"),
 *   description = @Translation("Use javascript asset url as source for media entities."),
 *   allowed_field_types = {"javascript_asset_url"}
 * )
 */
class JavascriptAsset extends MediaSourceBase {

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
      'type' => 'javascript_asset',
    ]);
  }

}
