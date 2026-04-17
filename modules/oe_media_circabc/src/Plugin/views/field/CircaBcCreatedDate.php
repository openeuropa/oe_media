<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Shows the created date of the publication.
 *
 * @ViewsField("circabc_created")
 */
class CircaBcCreatedDate extends CircaBcFieldBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (!$values->created) {
      return [];
    }

    $date = \DateTime::createFromFormat('Y-m-d\TH:i\Z', $values->created, new \DateTimeZone('UTC'));
    $date->setTimezone(new \DateTimeZone('Europe/Brussels'));
    return [
      '#markup' => \Drupal::service('date.formatter')->format($date->getTimestamp(), 'ewcms_admin_pages'),
    ];
  }

}
