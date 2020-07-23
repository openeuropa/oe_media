<?php

declare(strict_types = 1);

namespace Drupal\oe_media_iframe\Plugin\Validation\Constraint;

use Drupal\filter\Plugin\FilterInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the iframe media constraint.
 */
class IframeMediaConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $value */
    // Bail out if the source field is empty.
    if ($value->isEmpty()) {
      return;
    }

    $media = $value->getEntity();
    $text_format = $media->getSource()->getConfiguration()['text_format'];
    $iframe = $value->value;
    $iframe_filtered = check_markup($iframe, $text_format, '', [FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE])->__toString();
    if ($iframe !== $iframe_filtered) {
      $this->context->addViolation($constraint->message, [
        '%iframe' => $iframe,
        '%text_format' => $text_format,
      ]);
    }
  }

}
