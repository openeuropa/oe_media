<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\views\field;

use Drupal\entity_browser\Plugin\views\field\SelectForm;
use Drupal\views\ResultRow;

/**
 * Select form Views field for Entity Browser widgets.
 *
 * @ViewsField("circabc_select")
 */
class CircaBcSelectForm extends SelectForm {

  /**
   * {@inheritdoc}
   */
  public function getRowId(ResultRow $row) {
    return $row->uuid;
  }

}
