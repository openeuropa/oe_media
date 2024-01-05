<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\TranslationStatusInterface;
use Drupal\media\MediaInterface;
use Drupal\oe_media_circabc\CircaBc\CircaBcClientInterface;
use Drupal\oe_media_circabc\CircaBc\CircaBcDocument;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Maps the CircaBC document values to the Drupal media.
 */
class CircaBcMediaMapper {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The CircaBC client.
   *
   * @var \Drupal\oe_media_circabc\CircaBc\CircaBcClientInterface
   */
  protected $circaBcClient;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a CircaBcMediaMapper.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\oe_media_circabc\CircaBc\CircaBcClientInterface $circaBcClient
   *   The CircaBC client.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(LanguageManagerInterface $languageManager, CircaBcClientInterface $circaBcClient, EventDispatcherInterface $eventDispatcher) {
    $this->languageManager = $languageManager;
    $this->circaBcClient = $circaBcClient;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * Maps the values from the CircaBC document to the Drupal media.
   *
   * @param \Drupal\oe_media_circabc\CircaBc\CircaBcDocument $document
   *   The document.
   * @param \Drupal\media\MediaInterface $media
   *   The media.
   */
  public function mapDocumentMedia(CircaBcDocument $document, MediaInterface $media): void {
    $field_item = $media->get('oe_media_circabc_reference')->get(0);
    $field_item->size = $document->getProperty('size');
    $field_item->mime = $document->getProperty('mimetype');
    $field_item->filename = $document->getFileName();
    $title = $document->getTitle();
    if ($title && $title != "") {
      $media->set('name', $title);
    }

    // Set the created and changed dates.
    $created = \DateTime::createFromFormat('Y-m-d\TH:iZ', $document->getProperty('created'));
    $media->setCreatedTime($created->getTimestamp());
    $changed = \DateTime::createFromFormat('Y-m-d\TH:iZ', $document->getProperty('modified'));
    $media->setChangedTime($changed->getTimestamp());

    // Handle translations.
    if (!$media->isTranslatable() || !$this->languageManager->isMultilingual()) {
      // Bail out if the media itself is not translatable or the site is not
      // even multilingual.
      return;
    }

    if ($document->isMultilingual()) {
      $this->mapTranslations($document, $media);
    }

    $event = new CircaBcMapperEvent($document, $media);
    $this->eventDispatcher->dispatch($event, CircaBcMapperEvent::NAME);
  }

  /**
   * Maps the remote document translations to the local media.
   *
   * This should happen only if the document is multilingual.
   *
   * @param \Drupal\oe_media_circabc\CircaBc\CircaBcDocument $document
   *   The document.
   * @param \Drupal\media\MediaInterface $media
   *   The media.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  protected function mapTranslations(CircaBcDocument $document, MediaInterface $media): void {
    $this->circaBcClient->fillTranslations($document);
    foreach ($document->getTranslations() as $langcode => $document_translation) {
      $drupal_langcode = _oe_media_circabc_get_drupal_langcode($langcode);
      // Skip the language in case we are saving as part of a translation
      // delete.
      if ($media->getTranslationStatus($drupal_langcode) === TranslationStatusInterface::TRANSLATION_REMOVED) {
        continue;
      }

      if (!$this->languageManager->getLanguage($drupal_langcode)) {
        // The language could be disabled.
        continue;
      }
      $media_translation = $media->hasTranslation($drupal_langcode) ? $media->getTranslation($drupal_langcode) : $media->addTranslation($drupal_langcode, $media->toArray());
      if ($media->getFieldDefinition('oe_media_circabc_reference')->isTranslatable()) {
        $translation_field_item = $media_translation->get('oe_media_circabc_reference');
        $translation_field_item->uuid = $document_translation->getUuid();
        $translation_field_item->size = $document_translation->getProperty('size');
        $translation_field_item->mime = $document_translation->getProperty('mimetype');
        $translation_field_item->filename = $document_translation->getFileName();
      }

      if ($media->getFieldDefinition('name')->isTranslatable()) {
        $title = $document_translation->getTitle();
        if ($title && $title != "") {
          $media_translation->set('name', $title);
        }
      }

      // Set the created and changed dates if they are translatable.
      if ($media->getFieldDefinition('created')->isTranslatable()) {
        $created = \DateTime::createFromFormat('Y-m-d\TH:iZ', $document_translation->getProperty('created'));
        $media_translation->setCreatedTime($created->getTimestamp());
      }

      if ($media->getFieldDefinition('created')->isTranslatable()) {
        $changed = \DateTime::createFromFormat('Y-m-d\TH:iZ', $document_translation->getProperty('modified'));
        $media_translation->setChangedTime($changed->getTimestamp());
      }
    }
  }

}
