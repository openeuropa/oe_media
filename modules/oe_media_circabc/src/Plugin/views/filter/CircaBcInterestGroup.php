<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\views\filter;

use Drupal\oe_media_circabc\CircaBc\CircaBcClientInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter plugin for searching CircaBC by interest group.
 *
 * @ViewsFilter("circabc_interest_group")
 */
class CircaBcInterestGroup extends InOperator {

  /**
   * The CircaBC client.
   *
   * @var \Drupal\oe_media_circabc\CircaBc\CircaBcClientInterface
   */
  protected $circaBcClient;

  /**
   * Constructs a CircaBcInterestGroup object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\oe_media_circabc\CircaBc\CircaBcClientInterface $client
   *   The CircaBC client.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CircaBcClientInterface $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->circaBcClient = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('oe_media_circabc.client'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $interest_groups = $this->circaBcClient->getInterestGroups();
      if (!$interest_groups) {
        $this->valueOptions = [];
        return [];
      }

      foreach ($interest_groups as $info) {
        $this->valueOptions[$info['uuid']] = $info['name'];
      }
    }
    return $this->valueOptions;
  }

}
