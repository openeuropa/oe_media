<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\CircaBc;

/**
 * Model class for a CircaBC document.
 */
class CircaBcDocument {

  /**
   * The document data.
   *
   * @var array
   */
  protected $data = [];

  /**
   * The document translations.
   *
   * @var \Drupal\oe_media_circabc\CircaBc\CircaBcDocument[]
   */
  protected $translations = [];

  /**
   * Constructs a CircaBcDocument.
   *
   * @param array $data
   *   The data.
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * Gets the file name.
   *
   * @return string
   *   The name.
   */
  public function getFileName(): string {
    return $this->data['name'];
  }

  /**
   * Gets the UUID.
   *
   * @return string
   *   The UUID.
   */
  public function getUuid(): string {
    return $this->data['id'];
  }

  /**
   * Checks if the original document has any remote translations.
   *
   * @return bool
   *   Whether it has translations.
   */
  public function hasTranslations(): bool {
    return (int) $this->data['properties']['translations'] > 1;
  }

  /**
   * Gets the langcode of the document.
   *
   * @return string
   *   The langcode.
   */
  public function getLangcode(): string {
    // In case the value is in the format en_EN.
    return substr($this->data['properties']['locale'], 0, 2);
  }

  /**
   * Gets the title of the document in the document langcode.
   *
   * @return string
   *   The title.
   */
  public function getTitle(): string {
    $langcode = $this->getLangcode();
    return $this->data['title'][$langcode] ?? '';
  }

  /**
   * Gets the description of the document in the document langcode.
   *
   * @return string
   *   The title.
   */
  public function getDescription(): string {
    $langcode = $this->getLangcode();
    return $this->data['description'][$langcode] ?? '';
  }

  /**
   * Gets a specific property by name.
   *
   * @param string $name
   *   The property name.
   *
   * @return string|array
   *   The value.
   */
  public function getProperty(string $name): string|array {
    return $this->data['properties'][$name] ?? '';
  }

  /**
   * Gets the translation documents.
   *
   * @return \Drupal\oe_media_circabc\CircaBc\CircaBcDocument[]
   *   The translations.
   */
  public function getTranslations(): array {
    return $this->translations;
  }

  /**
   * Gets a translation document by language.
   *
   * @return \Drupal\oe_media_circabc\CircaBc\CircaBcDocument|null
   *   The translation.
   */
  public function getTranslation(string $langcode): ?CircaBcDocument {
    return $this->translations[$langcode] ?? NULL;
  }

  /**
   * Sets the translation documents onto the object.
   *
   * @param array $translations
   *   The translations.
   */
  public function setTranslations(array $translations): void {
    foreach ($translations as $translation) {
      $doc = new CircaBcDocument($translation);
      if ($doc->getUuid() === $this->getUuid()) {
        // We skip the current document from the list of translations.
        continue;
      }
      $this->translations[$doc->getLangcode()] = $doc;
    }
  }

  /**
   * Determines whether the document is multilingual.
   *
   * @return bool
   *   Whether the document is multilingual.
   */
  public function isMultilingual(): bool {
    return $this->getProperty('multilingual') === 'true';
  }

}
