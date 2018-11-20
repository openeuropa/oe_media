<?php

declare(strict_types = 1);

namespace Drupal\oe_media_avportal_test\EventSubscriber;

use Drupal\media_avportal_mock\AvPortalMockEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for the AV Portal mock.
 */
class AvPortalMockEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [AvPortalMockEvent::AV_PORTAL_MOCK_EVENT => 'setMockResources'];
  }

  /**
   * Sets a new JSON resource.
   *
   * @param \Drupal\media_avportal_mock\AvPortalMockEvent $event
   *   The event.
   */
  public function setMockResources(AvPortalMockEvent $event): void {
    $resources = $event->getResources();
    foreach (glob(drupal_get_path('module', 'oe_media_avportal_test') . '/responses/resources/*.json') as $file) {
      $ref = str_replace('.json', '', basename($file));
      $resources[$ref] = file_get_contents($file);
    }

    $event->setResources($resources);
  }

}
