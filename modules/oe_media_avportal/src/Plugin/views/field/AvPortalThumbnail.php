<?php

declare(strict_types = 1);

namespace Drupal\oe_media_avportal\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Defines a field for rendering the thumbnail of an AV Portal resource.
 *
 * @ViewsField("avportal_thumbnail")
 */
class AvPortalThumbnail extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return [
      '#theme' => 'image',
      '#uri' => $this->sanitizeValue($value),
      '#attributes' => [
        'width' => 300,
      ],
    ];
  }

}
