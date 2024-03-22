<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\StringFilter;

/**
 * Filter plugin for searching CircaBC by keywords.
 *
 * @ViewsFilter("circabc_text_search")
 */
class CircaBcTextSearch extends StringFilter {

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
