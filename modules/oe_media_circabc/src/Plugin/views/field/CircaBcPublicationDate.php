<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Shows the publication_date of the publication.
 *
 * @ViewsField("circabc_publication_date")
 */
class CircaBcPublicationDate extends CircaBcFieldBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (!$values->publication_date) {
      return [];
    }

    $date = \DateTime::createFromFormat('Y-m-d\TH:i\Z', $values->publication_date, new \DateTimeZone('UTC'));
    $date->setTimezone(new \DateTimeZone('Europe/Brussels'));
    return [
      '#markup' => \Drupal::service('date.formatter')->format($date->getTimestamp(), 'ewcms_admin_pages'),
    ];
  }

}
