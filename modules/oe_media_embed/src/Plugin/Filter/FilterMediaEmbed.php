<?php

declare(strict_types = 1);

namespace Drupal\oe_media_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Renderer;
use Drupal\embed\DomHelperTrait;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to convert PURL into internal urls/aliases.
 *
 * @Filter(
 *   id = "media_embed",
 *   title = @Translation("Embeds media entities using the oEmbed format"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterMediaEmbed extends FilterBase implements ContainerFactoryPluginInterface {

  use DomHelperTrait;

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
   * Constructs a new MediaEmbed object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, Renderer $renderer, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory->get('oe_media_embed.settings');
    $this->renderer = $renderer;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('renderer'),
      $container->get('entity_type.manager')
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

    $result->setProcessedText($this->serialize($dom));
    return $result;
  }

  /**
   * Replaces the default oEmbed markup with the meaningful rendered one.
   *
   * Since we are the owner of the oEmbed resolver and we are rendering local
   * media, for now we will load and render the local media with the selected
   * optional view mode rather than call the external resolver (which is in any
   * case internal).
   *
   * @param \DOMNode $node
   *   The DOM node element to replace.
   * @param \Drupal\filter\FilterProcessResult $result
   *   The processed result.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  protected function replaceOembedNode(\DOMNode $node, FilterProcessResult $result): void {
    $oembed = $node->getAttribute('data-oembed');
    $parsed = UrlHelper::parse($oembed);

    $service_url = $this->config->get('service_url');
    $resource_base_url = $this->config->get('resource_base_url');

    if (!isset($parsed['path']) || $parsed['path'] !== $service_url) {
      return;
    }

    if (!isset($parsed['query']['url']) || strpos($parsed['query']['url'], $resource_base_url) === FALSE) {
      return;
    }

    $parsed_resource_url = UrlHelper::parse($parsed['query']['url']);
    $regex = '/' . Uuid::VALID_PATTERN . '/';
    preg_match($regex, $parsed_resource_url['path'], $matches);
    if (!$matches) {
      return;
    }

    // If we reached this point, we will replace the node with something even
    // if the media is not found or the user doesn't have access to it. We don't
    // want anything displayed in these cases so we essentially kill the
    // embedded tag.
    $output = '';

    $uuid = $matches[0];
    $media = $this->entityTypeManager->getStorage('media')->loadByProperties(['uuid' => $uuid]);
    if (!$media) {
      $this->replaceNodeContent($node, $output);
      return;
    }

    $media = reset($media);

    $view_mode = $parsed_resource_url['query']['view_mode'] ?? 'default';
    $build = $this->entityTypeManager->getViewBuilder('media')->view($media, $view_mode);
    $cache = CacheableMetadata::createFromRenderArray($build);
    $access = $media->access('view', NULL, TRUE);
    $cache->addCacheableDependency($access);
    if ($access instanceof AccessResultAllowed) {
      $context = new RenderContext();
      $output = $this->renderer->executeInRenderContext($context, function () use (&$build) {
        return $this->renderer->render($build);
      });

      if (!$context->isEmpty()) {
        $result->addCacheableDependency($context->pop());
      }
    }

    $this->replaceNodeContent($node, $output);
  }

  /**
   * Converts the body of a \DOMDocument back to an HTML snippet.
   *
   * The function serializes the body part of a \DOMDocument back to an (X)HTML
   * snippet. The resulting (X)HTML snippet will be properly formatted to be
   * compatible with HTML user agents.
   *
   * @param \DOMDocument $document
   *   A \DOMDocument object to serialize, only the tags below the first <body>
   *   node will be converted.
   *
   * @return string
   *   A valid (X)HTML snippet, as a string.
   */
  private function serialize(\DOMDocument $document) {
    $body_node = $document->getElementsByTagName('body')->item(0);
    $html = '';

    if ($body_node !== NULL) {
      foreach ($body_node->childNodes as $node) {
        $html .= $document->saveHTML($node);
      }
    }
    return $html;
  }

}
