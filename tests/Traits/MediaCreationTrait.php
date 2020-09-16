<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Traits;

use Drupal\file\FileInterface;
use Drupal\media_avportal\Plugin\media\Source\MediaAvPortalSourceInterface;
use Drupal\media\Entity\Media;
use Drupal\Component\Utility\Html;

/**
 * Helper methods to deal with media creation.
 */
trait MediaCreationTrait {

  /**
   * Create a file entity from given file path.
   *
   * @param string $filepath
   *   Path to the file location.
   *
   * @return \Drupal\file\FileInterface
   *   File entity object.
   */
  protected function createFile(string $filepath): FileInterface {
    $file = file_save_data(file_get_contents($filepath), 'public://' . basename($filepath));
    $file->setPermanent();
    $file->save();

    return $file;
  }

  /**
   * Suggest a safe file name using the file uri.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   *
   * @return string
   *   The suggested name for the file entity.
   */
  protected function getFileNameSuggestion(FileInterface $file): string {
    return Html::cleanCssIdentifier($file->getFileUri());
  }

  /**
   * Create a media entity of image bundle.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   * @param array $settings
   *   An associative array of settings for the media entity.
   *
   * @return \Drupal\media\Entity\Media
   *   The media object.
   */
  protected function createMediaDocument(FileInterface $file, array $settings = []): Media {
    $settings += [
      'name' => $this->getFileNameSuggestion($file),
      'status' => 1,
      'uid' => 0,
    ];

    $values = [
      'bundle' => 'document',
      'name' => $settings['name'],
      'oe_media_file' => [
        'target_id' => (int) $file->id(),
      ],
      'status' => $settings['status'],
      'uid' => $settings['uid'],
    ];

    foreach (['name', 'status', 'uid'] as $key) {
      if (isset($settings[$key])) {
        // Remove already used values.
        unset($settings[$key]);
      }
    }

    $values += $settings;
    $media = \Drupal::entityTypeManager()
      ->getStorage('media')->create($values);
    $media->save();

    return $media;
  }

  /**
   * Create a media entity of image bundle.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   * @param array $settings
   *   An associative array of settings for the media entity.
   *
   * @return \Drupal\media\Entity\Media
   *   The media object.
   */
  protected function createMediaImage(FileInterface $file, array $settings = []): Media {
    $settings += [
      'name' => $this->getFileNameSuggestion($file),
      'file_id' => FALSE,
      'alt' => 'image',
      'title' => 'image',
      'status' => 1,
      'uid' => 0,
    ];

    $values = [
      'bundle' => 'image',
      'name' => $settings['name'],
      'oe_media_image' => [
        'target_id' => (int) $file->id(),
        'alt' => $settings['alt'],
        'title' => $settings['title'],
      ],
      'status' => $settings['status'],
      'uid' => $settings['uid'],
    ];

    foreach (['name', 'alt', 'title', 'status', 'uid'] as $key) {
      if (isset($settings[$key])) {
        // Remove already used values.
        unset($settings[$key]);
      }
    }

    $values += $settings;
    $media = \Drupal::entityTypeManager()
      ->getStorage('media')->create($values);
    $media->save();

    return $media;
  }

  /**
   * Create a media entity of av_portal_photo bundle.
   *
   * @param Drupal\media_avportal\Plugin\media\Source\MediaAvPortalSourceInterface $media_source
   *   The av_portal_photo media source.
   * @param array $settings
   *   An associative array of settings for the media entity.
   *
   * @return \Drupal\media\Entity\Media
   *   The media object.
   */
  protected function createMediaAvPortalPhoto(MediaAvPortalSourceInterface $media_source, array $settings = []): Media {
    $settings += [
      'status' => 1,
      'uid' => 0,
    ];

    $values = [
      'bundle' => 'av_portal_photo',
      'oe_media_avportal_photo' => $media_source->transformUrlToReference($settings['url']),
      'status' => $settings['status'],
      'uid' => $settings['uid'],
    ];

    foreach (['url', 'status', 'uid'] as $key) {
      if (isset($settings[$key])) {
        // Remove already used values.
        unset($settings[$key]);
      }
    }

    $values += $settings;
    $media = \Drupal::entityTypeManager()
      ->getStorage('media')->create($values);
    $media->save();

    return $media;
  }

  /**
   * Create a media entity of remote_video bundle.
   *
   * @param array $settings
   *   An associative array of settings for the media entity.
   *
   * @return \Drupal\media\Entity\Media
   *   The media object.
   */
  protected function createMediaRemoteVideo(array $settings = []): Media {
    $settings += [
      'status' => 1,
      'uid' => 0,
    ];

    $values = [
      'bundle' => 'remote_video',
      'oe_media_oembed_video' => $settings['url'],
      'status' => $settings['status'],
      'uid' => $settings['uid'],
    ];

    foreach (['url', 'status', 'uid'] as $key) {
      if (isset($settings[$key])) {
        // Remove already used values.
        unset($settings[$key]);
      }
    }

    $values += $settings;
    $media = \Drupal::entityTypeManager()
      ->getStorage('media')->create($values);
    $media->save();

    return $media;
  }

}
