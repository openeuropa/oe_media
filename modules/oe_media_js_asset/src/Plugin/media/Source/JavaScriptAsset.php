<?php

declare(strict_types=1);

namespace Drupal\oe_media_js_asset\Plugin\media\Source;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;

/**
 * JavaScript media source.
 *
 * @MediaSource(
 *   id = "oe_media_js_asset",
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

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldValue(MediaInterface $media) {
    $source_field = $this->configuration['source_field'];
    if (empty($source_field)) {
      throw new \RuntimeException('Source field for media source is not defined.');
    }

    $items = $media->get($source_field);
    if ($items->isEmpty()) {
      return NULL;
    }

    $field_item = $items->first();
    $values = $field_item->getValue();
    return implode(':', $values);
  }

}
