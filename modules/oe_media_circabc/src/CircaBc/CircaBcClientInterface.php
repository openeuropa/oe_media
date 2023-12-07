<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\CircaBc;

/**
 * Client interface for CircaBC.
 */
interface CircaBcClientInterface {

  /**
   * Returns document by URL.
   *
   * @param string $url
   *   The URL.
   *
   * @return \Drupal\oe_media_circabc\CircaBc\CircaBcDocument|null
   *   The circaBC document.
   */
  public function getDocumentByUrl(string $url): ?CircaBcDocument;

  /**
   * Returns document by UUID.
   *
   * @param string $uuid
   *   The UUID.
   *
   * @return \Drupal\oe_media_circabc\CircaBc\CircaBcDocument|null
   *   The circaBC document.
   */
  public function getDocumentByUuid(string $uuid): ?CircaBcDocument;

  /**
   * Fills a document with translations.
   *
   * @param \Drupal\oe_media_circabc\CircaBc\CircaBcDocument $document
   *   The circaBC document.
   */
  public function fillTranslations(CircaBcDocument $document): void;

}
