<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc;

use Drupal\media\MediaInterface;
use Drupal\oe_media_circabc\CircaBc\CircaBcDocument;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatcher during the mapping of a CircaBC document.
 */
class CircaBcMapperEvent extends Event {

  /**
   * The event name.
   */
  const NAME = 'oe_media_circabc.mapping_event';

  /**
   * The document.
   *
   * @var \Drupal\oe_media_circabc\CircaBc\CircaBcDocument
   */
  protected $document;

  /**
   * The media.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

  /**
   * Constructs a CircaBcMapperEvent.
   *
   * @param \Drupal\oe_media_circabc\CircaBc\CircaBcDocument $document
   *   The document.
   * @param \Drupal\media\MediaInterface $media
   *   The media.
   */
  public function __construct(CircaBcDocument $document, MediaInterface $media) {
    $this->document = $document;
    $this->media = $media;
  }

  /**
   * Returns the document.
   *
   * @return \Drupal\oe_media_circabc\CircaBc\CircaBcDocument
   *   The document.
   */
  public function getDocument(): CircaBcDocument {
    return $this->document;
  }

  /**
   * Returns the media.
   *
   * @return \Drupal\media\MediaInterface
   *   The media.
   */
  public function getMedia(): MediaInterface {
    return $this->media;
  }

}
