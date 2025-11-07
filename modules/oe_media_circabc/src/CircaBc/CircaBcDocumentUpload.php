<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\CircaBc;

use GuzzleHttp\Psr7\Utils;

/**
 * Contains the data necessary for a document upload.
 */
class CircaBcDocumentUpload {

  /**
   * The file location.
   *
   * @var string
   */
  protected string $fileLocation;

  /**
   * The language code.
   *
   * @var string
   */
  protected string $language;

  /**
   * Whether it's a pivot doc.
   *
   * @var bool
   */
  protected bool $pivot;

  /**
   * The fields.
   *
   * @var array
   */
  protected array $fields;

  /**
   * The translations.
   *
   * @var CircaBcDocumentUpload[]
   */
  protected array $translations = [];

  /**
   * Constructs a CircaBcDocumentUpload.
   *
   * @param string $fileLocation
   *   The file location.
   * @param string $language
   *   The language code.
   * @param bool $pivot
   *   Whether it's a pivot doc.
   * @param array $fields
   *   The fields.
   * @param array $translations
   *   The translations.
   */
  public function __construct(string $fileLocation, string $language, bool $pivot = FALSE, array $fields = [], array $translations = []) {
    $this->fileLocation = $fileLocation;
    $this->pivot = $pivot;
    $this->fields = $fields;
    $this->translations = $translations;
    $this->language = $language;
  }

  /**
   * Returns the file stream.
   *
   * @return resource
   *   The stream.
   */
  public function getFileStream() {
    return Utils::tryFopen($this->fileLocation, 'r');
  }

  /**
   * Returns the file name of the file.
   *
   * @return string
   *   The file name.
   */
  public function getFileName(): string {
    return basename($this->fileLocation);
  }

  /**
   * Returns whether it's a pivot file.
   *
   * @return bool
   *   If it's a pivot.
   */
  public function isPivot(): bool {
    return $this->pivot;
  }

  /**
   * Returns the fields.
   *
   * @return array
   *   The fields.
   */
  public function getFields(): array {
    return $this->fields;
  }

  /**
   * Returns the translations if any exist.
   *
   * @return CircaBcDocumentUpload[]
   *   The docs.
   */
  public function getTranslations(): array {
    return $this->translations;
  }

  /**
   * Returns the language.
   *
   * @return string
   *   The langcode.
   */
  public function getLanguage(): string {
    return $this->language;
  }

  /**
   * Sets the translations.
   *
   * @param array $translations
   *   The docs.
   */
  public function setTranslations(array $translations): void {
    $this->translations = $translations;
  }

}
