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

  /**
   * Queries for documents.
   *
   * @param string $uuid
   *   The category or interest group UUID.
   * @param string|null $langcode
   *   The langcode.
   * @param string|null $query_string
   *   A keyword for search.
   * @param array $filters
   *   Additional filters to apply.
   * @param int $page
   *   The pager page.
   * @param int $limit
   *   The pager limit.
   *
   * @return \Drupal\oe_media_circabc\CircaBc\CircaBcDocumentResult
   *   The results.
   */
  public function query(string $uuid, ?string $langcode = NULL, ?string $query_string = NULL, array $filters = [], int $page = 1, int $limit = 10): CircaBcDocumentResult;

  /**
   * Loads the available interest groups.
   *
   * @return array
   *   The interest group data.
   */
  public function getInterestGroups(): array;

  /**
   * Loads the data about an interest group.
   */
  public function getInterestGroup(string $id): array;

  /**
   * Uploads a document to CircaBC.
   *
   * @param \Drupal\oe_media_circabc\CircaBc\CircaBcDocumentUpload $document
   *   The document object.
   * @param string $interest_group
   *   The interest group directory to upload it to.
   */
  public function uploadDocument(CircaBcDocumentUpload $document, string $interest_group): string;

  /**
   * Uploads a document translation to CircaBC.
   *
   * @param \Drupal\oe_media_circabc\CircaBc\CircaBcDocumentUpload $document
   *   The document object.
   * @param string $pivot_id
   *   The pivot ID of the document to attach the translation to.
   */
  public function uploadDocumentTranslation(CircaBcDocumentUpload $document, string $pivot_id): string;

  /**
   * Deletes a CircaBC content by reference.
   *
   * @param string $ref
   *   The reference.
   */
  public function deleteContent(string $ref): void;

}
