<?php

declare(strict_types=1);

namespace Drupal\oe_media\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\oe_media\DocumentMediaFormHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the DocumentMediaConstraint.
 */
class DocumentMediaConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The document media form handler.
   *
   * @var \Drupal\oe_media\DocumentMediaFormHandler
   */
  protected $documentMediaFormHandler;

  /**
   * DocumentMediaConstraintValidator constructor.
   *
   * @param \Drupal\oe_media\DocumentMediaFormHandler $documentMediaFormHandler
   *   The document media form handler.
   */
  public function __construct(DocumentMediaFormHandler $documentMediaFormHandler) {
    $this->documentMediaFormHandler = $documentMediaFormHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('oe_media.document_media_form_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\media\MediaInterface $value */
    if ($value->bundle() !== 'document') {
      return;
    }

    if (!$this->documentMediaFormHandler->isFormDisplayConfigured()) {
      // We don't perform a validation if the form display is not configured
      // to show all the fields.
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
