<?php

declare(strict_types=1);

namespace Drupal\oe_media_oembed_mock\EventSubscriber;

use Drupal\oe_media_oembed_mock\OEmbedMockEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for the OEmbed mock.
 */
class OEmbedMockEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [OEmbedMockEvent::OEMBED_MOCK_EVENT => 'setMockResources'];
  }

  /**
   * Sets default JSON resources provided by the module.
   *
   * @param \Drupal\oe_media_oembed_mock\OEmbedMockEvent $event
   *   The event.
   */
  public function setMockResources(OEmbedMockEvent $event): void {
    $resources = $event->getResources();
    foreach ($event->getProviders() as $provider) {
      $files_pattern = \Drupal::service('extension.list.module')->getPath('oe_media_oembed_mock') . '/responses/resources/' . $provider . '/*.json';
      foreach (glob($files_pattern) as $file) {
        $ref = str_replace('.json', '', basename($file));
        $resources[$provider][$ref] = file_get_contents($file);
      }
    }

    $event->setResources($resources);
  }

}
