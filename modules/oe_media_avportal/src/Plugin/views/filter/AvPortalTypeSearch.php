<?php

declare(strict_types = 1);

namespace Drupal\oe_media_avportal\Plugin\views\filter;

use Drupal\media\MediaSourceManager;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter plugin for searching AV Portal by type.
 *
 * @ViewsFilter("avportal_type_search")
 */
class AvPortalTypeSearch extends InOperator {

  /**
   * The media source plugin manager.
   *
   * @var \Drupal\media\MediaSourceManager
   */
  protected $sourcePluginManager;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\media\MediaSourceManager $source_manager
   *   The media source plugin manager.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, MediaSourceManager $source_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sourcePluginManager = $source_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.media.source')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions(): ?array {
    $plugins = $this->sourcePluginManager->getDefinitions();
    foreach ($plugins as $plugin_id => $definition) {
      if (in_array('Drupal\media_avportal\Plugin\media\Source\MediaAvPortalSourceInterface', class_implements($definition['class']))) {
        $this->valueOptions[$plugin_id] = $definition['label'];
      }
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // We only support the "contains" operator.
    $this->opSimple();
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple(): void {
    if (empty($this->value)) {
      return;
    }

    $this->ensureMyTable();
    $this->query->addWhere((int) $this->options['group'], "$this->realField", array_values($this->value), $this->operator);
  }

}
