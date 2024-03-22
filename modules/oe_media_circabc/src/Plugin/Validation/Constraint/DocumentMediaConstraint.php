<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\Validation\Constraint;

use Drupal\oe_media\Plugin\Validation\Constraint\DocumentMediaConstraint as OriginalDocumentMediaConstraintAlias;

/**
 * Validates the Document media type.
 *
 * @Constraint(
 *   id = "CircaBcDocumentMedia",
 *   label = @Translation("Document type", context = "Validation"),
 *   type = "entity:media"
 * )
 */
class DocumentMediaConstraint extends OriginalDocumentMediaConstraintAlias {

  /**
   * Message shown when the CircaBC UUID is missing.
   *
   * @var string
   */
  public $messageMissingCircaBcReference = 'The document is configured to be CircaBC, please reference a correct reference.';

}
