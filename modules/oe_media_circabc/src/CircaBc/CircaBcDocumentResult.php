<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\CircaBc;

/**
 * Stores the result set of CircaBC documents.
 */
class CircaBcDocumentResult {

  /**
   * The documents as they come from the query.
   *
   * @var array|\Drupal\oe_media_circabc\CircaBc\CircaBcDocument[]
   */
  protected $documents = [];

  /**
   * The total results.
   *
   * @var int
   */
  protected int $total;

  /**
   * Constructs a CircaBcDocumentResult.
   *
   * @param \Drupal\oe_media_circabc\CircaBc\CircaBcDocument[] $documents
   *   The documents.
   * @param int $total
   *   The total.
   */
  public function __construct(array $documents = [], int $total = 0) {
    $this->documents = $documents;
    $this->total = $total;
  }

  /**
   * Returns the total.
   *
   * @return int
   *   The total.
   */
  public function getTotal(): int {
    return $this->total;
  }

  /**
   * Returns the documents.
   *
   * @return array|\Drupal\oe_media_circabc\CircaBc\CircaBcDocument[]
   *   The documents.
   */
  public function getDocuments(): array {
    return $this->documents;
  }

}
