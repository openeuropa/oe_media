<?php

declare(strict_types = 1);

namespace Drupal\oe_media;

use Drupal\Core\Field\BaseFieldDefinition;

class BundleFieldStorage extends BaseFieldDefinition {

  /**
   * {@inheritdoc}
   */
  public function isBaseField() {
    return FALSE;
  }

}
