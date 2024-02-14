<?php

declare(strict_types=1);

namespace Drupal\oe_media_link_lists\EventSubscriber;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\media\MediaInterface;
use Drupal\oe_link_lists\Event\EntityValueResolverEvent;
use Drupal\oe_link_lists_manual_source\Event\EntityValueOverrideResolverEvent;
use Drupal\oe_link_lists_manual_source\Event\ManualLinkResolverEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber that resolves internal media links from a manual link list.
 */
class InternalMediaLinksResolverSubscriber implements EventSubscriberInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * DefaultManualLinkResolverSubscriber constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher, EntityRepositoryInterface $entityRepository) {
    $this->eventDispatcher = $eventDispatcher;
    $this->entityRepository = $entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ManualLinkResolverEvent::NAME => ['resolveLink', 10],
    ];
  }

  /**
   * Resolves a manual internal media link object from a link list link entity.
   *
   * @param \Drupal\oe_link_lists_manual_source\Event\ManualLinkResolverEvent $event
   *   The event.
   */
  public function resolveLink(ManualLinkResolverEvent $event): void {
    $link_entity = $this->entityRepository->getTranslationFromContext($event->getLinkEntity());
    if ($link_entity->bundle() !== 'internal_media') {
      return;
    }

    $referenced_media = $link_entity->get('media_target')->entity;
    if (!$referenced_media instanceof MediaInterface) {
      // If the Media entity was deleted, we cannot resolve anything anymore.
      return;
    }

    /** @var \Drupal\media\MediaInterface $referenced_media */
    $referenced_media = $this->entityRepository->getTranslationFromContext($referenced_media);

    // Dispatch an event to turn the referenced media into a Link object.
    // We default to the oe_link_lists defaults and go with an empty teaser
    // as we don't have a field to map to the teaser. Modules that add fields
    // to the media can subscribe to this event and fill in the teaser.
    $resolver_event = new EntityValueResolverEvent($referenced_media);
    $this->eventDispatcher->dispatch($resolver_event, EntityValueResolverEvent::NAME);
    $link = $resolver_event->getLink();
    $link->addCacheableDependency($referenced_media);

    // Override the title and teaser if needed.
    if (!$link_entity->get('title')->isEmpty()) {
      $link->setTitle($link_entity->getTitle());
    }
    if (!$link_entity->get('teaser')->isEmpty()) {
      $link->setTeaser(['#markup' => $link_entity->getTeaser()]);
    }

    // Dispatch an event to allow others to perform overrides on the link.
    $override_event = new EntityValueOverrideResolverEvent($referenced_media, $link_entity, $link);
    $this->eventDispatcher->dispatch($override_event, EntityValueOverrideResolverEvent::NAME);
    $event->setLink($override_event->getLink());
    $event->stopPropagation();
  }

}
