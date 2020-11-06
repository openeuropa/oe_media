<?php

declare(strict_types = 1);

namespace Drupal\oe_media\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Validates the Document media type.
 *
 * @Constraint(
 *   id = "DocumentMedia",
 *   label = @Translation("Document type", context = "Validation"),
 *   type = "entity:media"
 * )
 */
class DocumentMediaConstraint extends CompositeConstraintBase {

  /**
   * Message shown when the local file is missing.
   *
   * @var string
   */
  public $messageMissingFile = 'The document is configured to be local, please upload a local file.';

  /**
   * Message shown when the remote file is missing.
   *
   * @var string
   */
  public $messageMissingRemoteFile = 'The document is configured to be remote, please reference a remote file.';

  /**
   * Message shown when the file type is missing.
   *
   * @var string
   */
  public $messageMissingFileType = 'The document file type is missing.';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['oe_media_file', 'oe_media_remote_file'];
  }

}
