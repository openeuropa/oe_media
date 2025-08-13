<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\views\query;

use Drupal\Core\Site\Settings;
use Drupal\oe_media_circabc\CircaBc\CircaBcClientInterface;
use Drupal\oe_media_circabc\CircaBc\CircaBcDocumentResult;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Query plugin for running queries against the CircaBC API.
 *
 * @ViewsQuery(
 *   id = "circabc",
 *   title = @Translation("CircaBC"),
 *   help = @Translation("Query against CircaBC API.")
 * )
 */
class CircaBcQuery extends QueryPluginBase {

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
   * The CircaBC client.
   *
   * phpcs:enable Drupal.NamingConventions.ValidVariableName.LowerCamelName
   *
   * @var \Drupal\oe_media_circabc\CircaBc\CircaBcClientInterface
   */
  protected $circaBcClient;

  /**
   * CircaBcQuery query constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin Id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\oe_media_circabc\CircaBc\CircaBcClientInterface $circaBcClient
   *   The CircaBC client.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CircaBcClientInterface $circaBcClient) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration = $configuration;
    $this->circaBcClient = $circaBcClient;
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
   * Ensures the table.
   *
   * Views expects the query backend to be SQL based. So we need to ignore this
   * by returning an empty string (we are not joining any tables).
   *
   * @param string $table
   *   The table to ensure.
   * @param string|null $relationship
   *   The relationship.
   *
   * @return string
   *   Table alias name.
   */
  public function ensureTable(string $table, ?string $relationship = NULL): string {
    return '';
  }

  /**
   * The fields to limit the results to.
   *
   * We don't limit the fields in CircaBC so we just return the name of the
   * field.
   *
   * @param string $table
   *   The table name.
   * @param string|null $field
   *   The field name.
   * @param string|null $alias
   *   The table alias.
   * @param array $params
   *   Optional params.
   *
   * @return string
   *   The field name.
   */
  public function addField(string $table, ?string $field = NULL, ?string $alias = NULL, array $params = []): string {
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
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function execute(ViewExecutable $view) {
    // Page the query.
    $limit = 10;
    $offset = 0;
    if (!empty($this->limit) || !empty($this->offset)) {
      // We can't have an offset without a limit, so provide a very large limit
      // instead.
      $limit = intval(!empty($this->limit) ? $this->limit : 999999);
      $offset = intval(!empty($this->offset) ? $this->offset : 0);
    }

    $page = (int) floor($offset / $limit + 1);
    // By default, the UUID is the category UUID across all interest groups.
    $uuid = Settings::get('circabc')['category'];
    $query_string = NULL;
    $langcode = NULL;
    $content_owner = NULL;

    // Filter by full text search.
    foreach ($this->where as $where) {
      foreach ($where['conditions'] as $condition) {
        if ($condition['field'] == 'search') {
          $query_string = $condition['value'];
        }
        if ($condition['field'] == 'language') {
          $value = $condition['value'][0];
          if (in_array($value, ['***LANGUAGE_language_interface***', '***LANGUAGE_site_default***'])) {
            $value = \Drupal::languageManager()->getDefaultLanguage()->getId();
          }

          $langcode = _oe_media_circabc_get_circabc_langcode($value);
        }

        if ($condition['field'] == 'interest_group') {
          $uuid = $condition['value'][0];
        }

        if ($condition['field'] == 'content_owner') {
          if (preg_match('/http:\/\/publications.europa.eu\/resource\/authority\/corporate-body\/([A-Z0-9_-]+)/', $condition['value'], $matches)) {
            // If the content contains an URL, extract the term code.
            $content_owner = $matches[1];
          }
          else {
            $content_owner = $condition['value'];
          }
        }
      }
    }

    $results = $this->circaBcClient->query($uuid, $langcode, $query_string, $content_owner, $page, $limit);
    if ($results->getTotal() === 0) {
      return;
    }

    $view->pager->total_items = $this->total_rows = $results->getTotal();
    $view->pager->postExecute($view->result);
    $view->pager->updatePageInfo();
    $this->createViewResults($results, $view);
  }

  /**
   * Creates the View results from the query results.
   *
   * @param \Drupal\oe_media_circabc\CircaBc\CircaBcDocumentResult $result
   *   The results.
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   */
  protected function createViewResults(CircaBcDocumentResult $result, ViewExecutable $view): void {
    $index = 0;

    /** @var \Drupal\oe_media_circabc\CircaBc\CircaBcDocument $document */
    foreach ($result->getDocuments() as $document) {
      $row = [];
      $row['uuid'] = $document->getUuid();
      $title = $document->getTitle();
      if ($title == "") {
        $title = $document->getFileName();
      }
      $row['title'] = $title;
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
   * @param string|null $field
   *   The condition field.
   * @param mixed $value
   *   The condition value.
   * @param string|null $operator
   *   The condition operator.
   */
  public function addWhere(int $group = 0, ?string $field = NULL, $value = NULL, ?string $operator = NULL): void {
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->setWhereGroup('AND', $group);
    }

    $this->where[$group]['conditions'][] = [
      'field' => trim($field, '.'),
      // SQL based '%' for LIKE filters need to be removed.
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

}
