<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\views\filter;

use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\Equality;

/**
 * Filter plugin for searching CircaBC by content owner.
 */
#[ViewsFilter("circabc_content_owner")]
class CircaBcContentOwner extends Equality {

}
