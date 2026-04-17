<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Shows the content owner of the document.
 *
 * @ViewsField("circabc_content_owner")
 */
class CircaBcContentOwner extends CircaBcFieldBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (!$values->content_owner) {
      return [];
    }

    $ids = explode(',', str_replace(['[', ']'], '', $values->content_owner));
    foreach ($ids as &$id) {
      $id = trim($id);
    }
    $labels = [];
    $content_owners = $this->entityTypeManager->hasDefinition('skos_concept') ? $this->entityTypeManager->getStorage('skos_concept')->loadMultiple($ids) : $values->content_owner;
    foreach ($content_owners as $content_owner) {
      $labels[] = $content_owner->label();
    }

    return [
      '#markup' => implode(', ', $labels),
    ];
  }

}
