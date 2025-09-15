<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\CircaBc;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;

/**
 * The client that connects with CircaBC.
 */
class CircaBcClient implements CircaBcClientInterface {

  /**
   * The filter array key for content owners, expects an array of term IDs.
   */
  public const FILTER_CONTENT_OWNERS = 'content_owners';

  /**
   * The filter array key for modification date "from", expects a \DateTime.
   *
   * It disregards the time part.
   */
  public const FILTER_MODIFIED_FROM = 'date_from';

  /**
   * The filter array key for modification date "to", expects a \DateTime.
   *
   * It disregards the time part.
   */
  public const FILTER_MODIFIED_TO = 'date_to';

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * The circaBC config.
   *
   * @var array
   */
  protected $config = [];

  /**
   * Static cache of document calls.
   *
   * @var array
   */
  protected $cache = [];

  /**
   * Constructs a CircaBcClient.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel factory.
   */
  public function __construct(ClientInterface $http_client, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->httpClient = $http_client;
    $this->config = Settings::get('circabc', []);
    $this->loggerChannelFactory = $loggerChannelFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getDocumentByUrl(string $url): ?CircaBcDocument {
    $uuid = $this->extractUuidFromUrl($url);
    if ($uuid == "") {
      $this->loggerChannelFactory->get('oe_media_circabc')->error(sprintf('The URL %s does not contain a valid CircaBC document reference.', $url));
      return NULL;
    }

    return $this->getDocumentByUuid($uuid);
  }

  /**
   * {@inheritdoc}
   */
  public function getDocumentByUuid(string $uuid): ?CircaBcDocument {
    if (isset($this->cache[$uuid])) {
      return $this->cache[$uuid];
    }

    if (!isset($this->config['url'])) {
      $this->loggerChannelFactory->get('oe_media_circabc')->error('The CircaBC URL is not configured');
      return NULL;
    }

    $endpoint = $this->config['url'] . '/service/circabc/nodes/' . $uuid;
    $url = Url::fromUri($endpoint, [
      'query' => [
        'guest' => "true",
      ],
    ])->toString();
    try {
      $response = $this->httpClient->request('GET', $url);
      if ($response->getStatusCode() !== 200) {
        $this->loggerChannelFactory->get('oe_media_circabc')->error($response->getBody()->getContents());

        return NULL;
      }
    }
    catch (\Exception $exception) {
      $this->loggerChannelFactory->get('oe_media_circabc')->error($exception->getMessage());
      return NULL;
    }

    $content = json_decode($response->getBody()->getContents(), TRUE);
    if (!$content) {
      $this->loggerChannelFactory->get('oe_media_circabc')->error($response->getBody()->getContents());
      return NULL;
    }
    $document = new CircaBcDocument($content);
    $this->cache[$document->getUuid()] = $document;
    return $document;
  }

  /**
   * {@inheritdoc}
   */
  public function fillTranslations(CircaBcDocument $document): void {
    $endpoint = $this->config['url'] . '/service/circabc/content/' . $document->getUuid() . '/translations';
    $url = Url::fromUri($endpoint, [
      'query' => [
        'guest' => "true",
      ],
    ])->toString();
    $response = $this->httpClient->request('GET', $url);
    if ($response->getStatusCode() !== 200) {
      return;
    }

    $content = json_decode($response->getBody()->getContents(), TRUE);
    $document->setTranslations($content['translations']);
  }

  /**
   * {@inheritdoc}
   */
  public function query(string $uuid, ?string $langcode = NULL, ?string $query_string = NULL, array $filters = [], int $page = 1, int $limit = 10): CircaBcDocumentResult {
    $endpoint = $this->config['url'] . '/service/circabc/files';
    $query = [
      'node' => $uuid,
      'page' => $page,
      'limit' => $limit,
    ];
    if ($langcode) {
      $query['language'] = $langcode;
    }
    if ($query_string) {
      $query['q'] = $query_string;
    }
    if (!empty($filters[static::FILTER_CONTENT_OWNERS])) {
      $query['contentOwners'] = implode(',', $filters[static::FILTER_CONTENT_OWNERS]);
    }
    if (!empty($filters[static::FILTER_MODIFIED_FROM]) && $filters[static::FILTER_MODIFIED_FROM] instanceof \DateTime) {
      $query['from'] = $filters[static::FILTER_MODIFIED_FROM]->format('Y-m-d');
    }
    if (!empty($filters[static::FILTER_MODIFIED_TO]) && $filters[static::FILTER_MODIFIED_TO] instanceof \DateTime) {
      $query['to'] = $filters[static::FILTER_MODIFIED_TO]->format('Y-m-d');
    }

    $url = Url::fromUri($endpoint, [
      'query' => $query,
    ])->toString();

    try {
      $auth = 'Basic ' . base64_encode($this->config['username'] . ':' . $this->config['password']);
      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'Authorization' => $auth,
        ],
      ]);
      if ($response->getStatusCode() !== 200) {
        $this->loggerChannelFactory->get('oe_media_circabc')->error($response->getBody()->getContents());

        return new CircaBcDocumentResult();
      }
    }
    catch (\Exception $exception) {
      $this->loggerChannelFactory->get('oe_media_circabc')->error($exception->getMessage());

      return new CircaBcDocumentResult();
    }

    $content = json_decode($response->getBody()->getContents(), TRUE);
    if (!$content) {
      $this->loggerChannelFactory->get('oe_media_circabc')->error($response->getBody()->getContents());
      return new CircaBcDocumentResult();
    }

    $documents = [];
    foreach ($content['data'] as $document_values) {
      $documents[] = new CircaBcDocument($document_values);
    }

    return new CircaBcDocumentResult($documents, $content['total']);
  }

  /**
   * {@inheritdoc}
   */
  public function getInterestGroups(): array {
    $url = $this->config['url'] . '/service/circabc/categories/' . $this->config['category'] . '/groups';

    try {
      $auth = 'Basic ' . base64_encode($this->config['username'] . ':' . $this->config['password']);
      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'Authorization' => $auth,
        ],
      ]);
      if ($response->getStatusCode() !== 200) {
        $this->loggerChannelFactory->get('oe_media_circabc')->error($response->getBody()->getContents());

        return [];
      }
    }
    catch (\Exception $exception) {
      $this->loggerChannelFactory->get('oe_media_circabc')->error($exception->getMessage());

      return [];
    }

    $content = json_decode($response->getBody()->getContents(), TRUE);
    if (!$content) {
      $this->loggerChannelFactory->get('oe_media_circabc')->error($response->getBody()->getContents());
      return [];
    }

    $groups = [];
    foreach ($content as $values) {
      $groups[] = [
        'uuid' => $values['id'],
        'name' => $values['name'],
        'data' => $values,
      ];
    }

    return $groups;
  }

  /**
   * Extracts the UUID from the URL.
   *
   * @param string $url
   *   The URL.
   *
   * @return string
   *   The UUID.
   */
  protected function extractUuidFromUrl(string $url): string {
    $parsed = parse_url($url);
    $parts = explode('/', $parsed['path']);
    if (!$parts) {
      return '';
    }
    array_pop($parts);
    if (!$parts) {
      return '';
    }
    $uuid = end($parts);
    if (!Uuid::isValid($uuid)) {
      return '';
    }

    return $uuid;
  }

}
