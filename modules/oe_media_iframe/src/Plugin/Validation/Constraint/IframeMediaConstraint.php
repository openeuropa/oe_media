<?php

declare(strict_types = 1);

namespace Drupal\oe_media_iframe\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if iframe media is valid according to the selected text format.
 *
 * @Constraint(
 *   id = "IframeMedia",
 *   label = @Translation("Iframe Media Constraint", context = "Validation"),
 *   type = {"entity"}
 * )
 */
class IframeMediaConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The iframe %iframe code contains html tags or attributes that are not allowed in the %text_format text format.';

}
