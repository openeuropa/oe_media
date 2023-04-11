<?php

declare(strict_types = 1);

namespace Drupal\oe_media_gallery\Plugin\LinkSource;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\oe_link_lists\Event\EntityValueResolverEvent;
use Drupal\oe_link_lists\LinkCollection;
use Drupal\oe_link_lists\LinkCollectionInterface;
use Drupal\oe_link_lists\LinkSourcePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Base class for the Gallery link source plugins.
 */
abstract class GalleryLinkSourceBase extends LinkSourcePluginBase implements ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a GalleryLinkSourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventDispatcher = $event_dispatcher;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'media' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // We do nothing here because the media selection is handled via the
    // oe_media_gallery_media field.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here as we copy the referenced link IDs to the plugin
    // configuration inside preSave().
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(ContentEntityInterface $entity): void {
    parent::preSave($entity);

    if ($entity->get('oe_media_gallery_media')->isEmpty()) {
      // If there are no referenced media we don't have to do anything.
      return;
    }

    /** @var \Drupal\media\MediaInterface[] $media_entities */
    $media_entities = $entity->get('oe_media_gallery_media')->referencedEntities();

    // Set the referenced media IDS onto the plugin configuration.
    $ids = [];
    foreach ($media_entities as $media_entity) {
      $ids[] = $media_entity->id();
    }

    /** @var \Drupal\oe_link_lists\Entity\LinkListInterface $entity */
    $configuration = $entity->getConfiguration();
    $configuration['source']['plugin_configuration']['media'] = $ids;
    $entity->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getLinks(int $limit = NULL, int $offset = 0): LinkCollectionInterface {
    $links = new LinkCollection();
    $ids = $this->configuration['media'];
    if (!$ids) {
      return $links;
    }

    $ids = array_slice($ids, $offset, $limit);
    /** @var \Drupal\media\MediaInterface[] $media_entities */
    $media_entities = $this->entityTypeManager->getStorage('media')->loadMultiple($ids);

    $links = new LinkCollection();
    foreach ($media_entities as $media_entity) {
      $event = new EntityValueResolverEvent($media_entity);
      $this->eventDispatcher->dispatch($event, EntityValueResolverEvent::NAME);
      $link = $event->getLink();
      $link->addCacheableDependency($media_entity);
      $links->add($link);
    }

    return $links;
  }

}
