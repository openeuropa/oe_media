<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\Validation\Constraint;

use Drupal\oe_media\Plugin\Validation\Constraint\DocumentMediaConstraintValidator as OriginalDocumentMediaConstraintValidatorAlias;
use Symfony\Component\Validator\Constraint;

/**
 * Validates the CircaBcDocumentMediaConstraint.
 */
class DocumentMediaConstraintValidator extends OriginalDocumentMediaConstraintValidatorAlias {

  /**
   * {@inheritdoc}
   */
  protected function getRequiredFieldMap(): array {
    return parent::getRequiredFieldMap() + [
      'circabc' => 'oe_media_circabc_reference',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMessageMap(Constraint $constraint): array {
    return parent::getMessageMap($constraint) + [
      'circabc' => $constraint->messageMissingCircaBcReference,
    ];
  }

}
