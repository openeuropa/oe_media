<?php

namespace Drupal\oe_media_avportal\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\StringFilter;

/**
 * Filter plugin for searching AV Portal by keywords.
 *
 * @ViewsFilter("avportal_text_search")
 */
class AvPortalTextSearch extends StringFilter {

  /**
   * {@inheritdoc}
   */
  public function operators() {
    return ['contains' => parent::operators()['contains']];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // We only support the "contains" operator.
    $this->opContains($this->realField);
  }

}