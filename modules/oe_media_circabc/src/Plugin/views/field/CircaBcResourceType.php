<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Shows the resource_type of the document.
 *
 * @ViewsField("circabc_resource_type")
 */
class CircaBcResourceType extends CircaBcFieldBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (!$values->resource_type) {
      return [];
    }

    return [
      '#markup' => $this->entityTypeManager->hasDefinition('skos_concept') ? $this->entityTypeManager->getStorage('skos_concept')->load($values->resource_type)->label() : $values->resource_type,
    ];
  }

}
