<?php

declare(strict_types = 1);

namespace Drupal\oe_media_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\embed\DomHelperTrait;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\media\MediaInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a filter to convert PURL into internal urls/aliases.
 *
 * @Filter(
 *   id = "media_embed",
 *   title = @Translation("Embeds media entities using the oEmbed format"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class MediaEmbed extends FilterBase implements ContainerFactoryPluginInterface {

  use DomHelperTrait;

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The general module settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new MediaEmbed object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $client, ConfigFactoryInterface $config_factory, Renderer $renderer, EntityTypeManagerInterface $entityTypeManager, RequestStack $requestStack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
    $this->config = $config_factory->get('oe_media_embed.settings');
    $this->renderer = $renderer;
    $this->entityTypeManager = $entityTypeManager;
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (strpos($text, 'data-oembed') === FALSE) {
      return $result;
    }

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);

    foreach ($xpath->query('//p[@data-oembed]') as $node) {
      $this->replaceOembedNode($node, $result);
    }

    $result->setProcessedText(Html::serialize($dom));
    return $result;
  }

  /**
   * Replaces the default oEmbed markup with the meaningful rendered one.
   *
   * @param \DOMNode $node
   *   The DOM node element to replace.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  protected function replaceOembedNode(\DOMNode $node): void {
    $oembed = $node->getAttribute('data-oembed');
    $parsed = UrlHelper::parse($oembed);

    // Replace the service and resource URLs.
    $service_url = $this->config->get('service_url');
    $resource_base_url = $this->config->get('resource_base_url');

    if (!isset($parsed['path']) || $parsed['path'] !== $service_url) {
      return;
    }

    if (!isset($parsed['query']) || !$parsed['query'] || !isset($parsed['query']['url']) || strpos($parsed['query']['url'], $resource_base_url) === FALSE) {
      return;
    }

    $base_url = $this->request->getSchemeAndHttpHost() . $this->request->getBaseUrl();
    $new_service_url = str_replace($service_url, $base_url, $parsed['path']) . '/oembed';
    $new_resource_url = str_replace($resource_base_url, $base_url . '/', $parsed['query']['url']);

    $url = Url::fromUri($new_service_url, ['query' => ['url' => $new_resource_url]]);
    $generated_url = $url->toString(TRUE);
    try {
      $response = $this->client->request('GET', $generated_url->getGeneratedUrl());
    }
    catch (\Exception $exception) {
      return;
    }

    if ($response->getStatusCode() !== 200) {
      return;
    }

    $json = json_decode($response->getBody()->__toString(), TRUE);
    if (!isset($json['version'])) {
      return;
    }

    $output = $this->buildMediaEmbedFromJsonResponse($json);
    if ($output) {
      $this->replaceNodeContent($node, $output);
    }
  }

  /**
   * Turns the JSON response from the oEmbed service in to the render value.
   *
   * There are a few supported responses: link, photo, video, rich.
   *
   * - link is used for File downloads
   * - photo is used for image tags
   * - video and rich are used for more complex structures where the render
   * value is in the response.
   *
   * @param array $response
   *   The array containing the response to be used.
   *
   * @return string|null
   *   The embeddable string or null if not possible.
   */
  protected function buildMediaEmbedFromJsonResponse(array $response): ?string {
    $build = [];

    switch ($response['type']) {
      // File media.
      case 'link':
        if (!$response['mid']) {
          return NULL;
        }

        $media = $this->entityTypeManager->getStorage('media')->load($response['mid']);
        if (!$media instanceof MediaInterface) {
          return NULL;
        }

        $build = $this->entityTypeManager->getViewBuilder('media')->view($media);

        break;

      // Image based media.
      case 'photo':
        if (!$response['url']) {
          return NULL;
        }

        $build = [
          '#theme' => 'image',
          '#uri' => $response['url'],
        ];

        break;

      // Any rich text media.
      case 'video':
      case 'rich':
        return $response['html'];
    }

    if ($build) {
      $output = $this->renderer->executeInRenderContext(new RenderContext(), function () use (&$build) {
        return $this->renderer->render($build);
      });

      return (string) $output;
    }

    return NULL;
  }

}
