<?php

namespace Drupal\oe_media_avportal\Plugin\views\field;

use Drupal\entity_browser\Plugin\views\field\SelectForm;
use Drupal\views\ResultRow;

/**
 * Select form Views field for Entity Browser widgets.
 *
 * @ViewsField("avportal_select")
 */
class AvPortalSelectForm extends SelectForm {

  /**
   * {@inheritdoc}
   */
  public function getRowId(ResultRow $row) {
    return $row->ref;
  }

}
