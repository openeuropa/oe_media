<?php

declare(strict_types = 1);

namespace Drupal\oe_media_avportal\Plugin\views\query;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_avportal\AvPortalClientFactory;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Query plugin for running queries against the AV Portal API.
 *
 * @ViewsQuery(
 *   id = "avportal",
 *   title = @Translation("AV Portal"),
 *   help = @Translation("Query against AV Portal API.")
 * )
 */
class AVPortalQuery extends QueryPluginBase {

  /**
   * The query where group.
   *
   * @var array
   */
  public $where;

  /**
   * The total number of rows in the query.
   *
   * @var int
   *
   * phpcs:disable Drupal.NamingConventions.ValidVariableName.LowerCamelName
   */
  public $total_rows;

  /**
   * AV Portal client factory.
   *
   * @var \Drupal\media_avportal\AvPortalClientFactory
   */
  protected $clientFactory;

  /**
   * The AP Portal settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * AV Portal query constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin Id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\media_avportal\AvPortalClientFactory $client_factory
   *   The AV Portal client factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleExtensionList|null $moduleExtensionList
   *   The module extension list.
   *
   *  phpcs:disable Drupal.Semantics.FunctionTriggerError.TriggerErrorTextLayoutRelaxed
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AvPortalClientFactory $client_factory, ConfigFactoryInterface $config_factory, ModuleExtensionList $moduleExtensionList = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->clientFactory = $client_factory;
    $this->config = $config_factory->get('media_avportal.settings');

    if (!$moduleExtensionList) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $moduleExtensionList argument is deprecated in 1.23.0 and will be required in 2.0.0.', E_USER_DEPRECATED);
      $moduleExtensionList = \Drupal::service('extension.list.module');
    }

    $this->moduleExtensionList = $moduleExtensionList;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('media_avportal.client_factory'),
      $container->get('config.factory'),
      $container->get('extension.list.module')
    );
  }

  /**
   * Ensures the table.
   *
   * Views expects the query backend to be SQL based. So we need to ignore this
   * by returning an empty string (we are not joining any tables).
   *
   * @param string $table
   *   The table to ensure.
   * @param string $relationship
   *   The relationship.
   *
   * @return string
   *   Table alias name.
   */
  public function ensureTable(string $table, string $relationship = NULL): string {
    return '';
  }

  /**
   * The fields to limit the results to.
   *
   * We don't limit the fields in AV Portal so we just return the name of the
   * field.
   *
   * @param string $table
   *   The table name.
   * @param string $field
   *   The field name.
   * @param string $alias
   *   The table alias.
   * @param array $params
   *   Optional params.
   *
   * @return string
   *   The field name.
   */
  public function addField(string $table, string $field = NULL, string $alias = NULL, array $params = []): string {
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function query($get_count = FALSE) {
    // We don't perform a query.
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function execute(ViewExecutable $view) {
    $options = [];

    // Page the query.
    if (!empty($this->limit) || !empty($this->offset)) {
      // We can't have an offset without a limit, so provide a very large limit
      // instead.
      $limit = intval(!empty($this->limit) ? $this->limit : 999999);
      $offset = intval(!empty($this->offset) ? $this->offset : 0);
      $options = [
        'index' => $offset + 1,
        'pagesize' => $limit,
      ];
    }

    // Filter by full text search.
    foreach ($this->where as $where) {
      foreach ($where['conditions'] as $condition) {
        if ($condition['field'] == 'search') {
          $options['kwgg'] = $condition['value'];
        }
        if ($condition['field'] == 'type') {
          $types = [];
          if (in_array('media_avportal_video', $condition['value'])) {
            $types[] = 'VIDEO';
          }
          if (in_array('media_avportal_photo', $condition['value'])) {
            $types[] = 'PHOTO';
          }
          $options['type'] = implode(',', $types);
        }
      }
    }

    $client = $this->clientFactory->getClient([
      'use_cache' => !empty($this->options['cache_query_response']),
    ]);
    $results = $client->query($options);
    if ($results['num_found'] === 0) {
      return;
    }

    $view->pager->total_items = $this->total_rows = $results['num_found'];
    $view->pager->postExecute($view->result);
    $view->pager->updatePageInfo();
    $this->createViewResults($results, $view);
  }

  /**
   * Creates the View results from the query results.
   *
   * @param array $results
   *   The results.
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   */
  protected function createViewResults(array $results, ViewExecutable $view): void {
    $index = 0;

    /** @var \Drupal\media_avportal\AvPortalResource $resource */
    foreach ($results['resources'] as $resource) {
      $row = [];
      $row['ref'] = $resource->getRef();
      $row['title'] = $resource->getTitle();
      $row['type'] = $resource->getType();
      $row['thumbnail'] = $resource->getThumbnailUrl() ?? $this->moduleExtensionList->getPath('media') . '/images/icons/no-thumbnail.png';

      if (in_array($resource->getType(), ['PHOTO', 'REPORTAGE'])) {
        $row['thumbnail'] = $this->config->get('photos_base_uri') . $row['thumbnail'];
      }

      $row['index'] = $index;
      $view->result[] = new ResultRow($row);
      $index++;
    }
  }

  /**
   * This is called by the filter plugins to set the query conditions.
   *
   * @param int $group
   *   The where group.
   * @param string $field
   *   The condition field.
   * @param mixed $value
   *   The condition value.
   * @param string $operator
   *   The condition operator.
   */
  public function addWhere(int $group = 0, string $field = NULL, $value = NULL, string $operator = NULL): void {
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->setWhereGroup('AND', $group);
    }

    $this->where[$group]['conditions'][] = [
      'field' => $field,
      // SQL based '%' for LIKE filters need to be removed. In AV Portal
      // it's always LIKE.
      'value' => is_string($value) ? trim($value, '%') : $value,
      'operator' => $operator,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(ViewExecutable $view) {
    $this->view = $view;
    $view->initPager();
    $view->pager->query();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['cache_query_response'] = [
      'default' => FALSE,
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['cache_query_response'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use cache for AV Portal queries response'),
      '#description' => $this->t('Whether or not to cache the responses for the queries executed. If checked, the responses will be cached by the media_avportal module.'),
      '#default_value' => !empty($this->options['cache_query_response']),
    ];
  }

}
