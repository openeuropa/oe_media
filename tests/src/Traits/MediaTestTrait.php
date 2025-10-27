<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\Traits;

use Behat\Mink\Element\NodeElement;
use Drupal\media\MediaInterface;

/**
 * Trait for testing media.
 */
trait MediaTestTrait {

  /**
   * Retrieves a media entity by its name.
   *
   * @param string $name
   *   The media name.
   * @param bool $reset
   *   Whether to reset the cache.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function getMediaByName(string $name, bool $reset = TRUE): MediaInterface {
    $storage = \Drupal::entityTypeManager()->getStorage('media');
    if ($reset) {
      $storage->resetCache();
    }
    $media = $storage->loadByProperties([
      'name' => $name,
    ]);

    if (!$media) {
      throw new \Exception("Could not find media with name '$name'.");
    }

    if (count($media) > 1) {
      throw new \Exception("Multiple medias with name '$name' found.");
    }

    return reset($media);
  }

  /**
   * Returns the options of a select element as an associative array.
   *
   * @param \Behat\Mink\Element\NodeElement $select
   *   The select element.
   *
   * @return array
   *   An associative array of the select options, keyed by option value.
   */
  protected function getSelectOptions(NodeElement $select): array {
    $options = [];
    foreach ($select->findAll('css', 'option') as $option) {
      $options[$option->getValue()] = $option->getText();
    }
    return $options;
  }

}
