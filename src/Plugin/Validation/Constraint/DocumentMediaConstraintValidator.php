<?php

declare(strict_types = 1);

namespace Drupal\oe_media\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the DocumentMediaConstraint.
 */
class DocumentMediaConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\media\MediaInterface $value */
    if ($value->bundle() !== 'document') {
      return;
    }

    $file_type = $value->get('oe_media_file_type')->value;
    $required_map = [
      'local' => 'oe_media_file',
      'remote' => 'oe_media_remote_file',
    ];
    $message_map = [
      'local' => $constraint->messageMissingFile,
      'remote' => $constraint->messageMissingRemoteFile,
    ];

    if (!$file_type) {
      $this->context->buildViolation($constraint->messageMissingFileType)
        ->atPath('oe_media_file_type')
        ->addViolation();

      return;
    }

    if ($value->get($required_map[$file_type])->isEmpty()) {
      $this->context->buildViolation($message_map[$file_type])
        ->atPath($required_map[$file_type])
        ->addViolation();
    }
  }

}
