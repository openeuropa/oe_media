<?php

declare(strict_types = 1);

namespace Drupal\oe_media_avportal\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter plugin for searching AV Portal by type.
 *
 * @ViewsFilter("avportal_type_search")
 */
class AvPortalTypeSearch extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $source_manager) {
    $this->sourceManager = $source_manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('plugin.manager.media.source'));
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions(): ?array {

    $plugins = $this->sourceManager->getDefinitions();
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
    $this->opSimple($this->realField);
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple(): void {
    if (empty($this->value)) {
      return;
    }
    $this->ensureMyTable();

    // We use array_values() because the checkboxes keep keys and that can cause
    // array addition problems.
    $this->query->addWhere($this->options['group'], "$this->realField", implode(',', array_values($this->value)), $this->operator);
  }

}
