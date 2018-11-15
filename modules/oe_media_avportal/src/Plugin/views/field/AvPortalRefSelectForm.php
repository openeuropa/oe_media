<?php

namespace Drupal\oe_media_avportal\Plugin\views\field;

use Drupal\entity_browser\Plugin\views\field\SelectForm;
use Drupal\views\ResultRow;

/**
 * Select form that allows using the view with an entity browser.
 *
 * Extending from the default entity SelectForm but altering the row ID value
 * as AV Portal resources are not Drupal entities.
 *
 * @ViewsField("avportal_select")
 */
class AvPortalRefSelectForm extends SelectForm {

  /**
   * {@inheritdoc}
   */
  public function getRowId(ResultRow $row) {
    return $row->ref;
  }
}
