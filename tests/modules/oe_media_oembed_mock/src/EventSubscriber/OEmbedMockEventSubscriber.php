<?php

declare(strict_types = 1);

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
   * Sets a new JSON resource.
   *
   * @param \Drupal\media_avportal_mock\AvPortalMockEvent $event
   *   The event.
   */
  public function setMockResources(OEmbedMockEvent $event): void {
    $resources = $event->getResources();
    foreach ($event->getProviders() as $provider) {
      foreach (glob(drupal_get_path('module', 'oe_media_oembed_mock') . '/responses/resources/' . $provider . '/*.json') as $file) {
        $ref = str_replace('.json', '', basename($file));
        $resources[$provider][$ref] = file_get_contents($file);
      }
    }

    $event->setResources($resources);
  }

}
