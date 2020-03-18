<?php

declare(strict_types = 1);

namespace Drupal\oe_media;

use Drupal\media\Plugin\views\filter\Status as CoreStatus;

/**
 * Override the Views Status filter for Media entities.
 *
 * The core Status filter checks for a few permissions the user has in order
 * to view a given Media entity in the list. We are adding an extra permission,
 * called 'view any unpublished media'.
 */
class Status extends CoreStatus {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $table = $this->ensureMyTable();
    $snippet = "$table.status = 1 OR ($table.uid = ***CURRENT_USER*** AND ***CURRENT_USER*** <> 0 AND ***VIEW_OWN_UNPUBLISHED_MEDIA*** = 1) OR ***ADMINISTER_MEDIA*** = 1 OR ***VIEW_ANY_UNPUBLISHED_MEDIA*** = 1";
    if ($this->moduleHandler->moduleExists('content_moderation')) {
      $snippet .= ' OR ***VIEW_ANY_UNPUBLISHED_NODES*** = 1';
    }
    $this->query->addWhereExpression($this->options['group'], $snippet);
  }

}
