<?php

declare(strict_types=1);

namespace Drupal\oe_media_oembed_mock;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A Guzzle middleware for intercepting oEmbed endpoints.
 */
class OEmbedClientMiddleware {

  /**
   * The media config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * The list of allowed providers.
   *
   * @var array
   */
  protected $allowedProviders = [
    'youtube' => 'www.youtube.com',
    'vimeo' => 'vimeo.com',
    'dailymotion' => 'www.dailymotion.com',

  ];

  /**
   * The list of hosts where thumbnails are retrieved from.
   *
   * @var array
   */
  protected $thumbnailHosts = [
    'i.ytimg.com',
    'i.vimeocdn.com',
  ];

  /**
   * OEmbedClientMiddleware constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Extension\ModuleExtensionList|null $moduleExtensionList
   *   The module extension list.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EventDispatcherInterface $eventDispatcher, ?ModuleExtensionList $moduleExtensionList = NULL) {
    $this->config = $configFactory->get('media.settings');
    $this->eventDispatcher = $eventDispatcher;

    // @codingStandardsIgnoreStart
    if (!$moduleExtensionList) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $moduleExtensionList argument is deprecated in 1.23.0 and will be required in 2.0.0.', E_USER_DEPRECATED);
      $moduleExtensionList = \Drupal::service('extension.list.module');
    }
    // @codingStandardsIgnoreEnd

    $this->moduleExtensionList = $moduleExtensionList;
  }

  /**
   * HTTP middleware that returns pre-saved data for oEmbed requests.
   */
  public function __invoke() {
    // For oEmbed requests, we need to skip the execution to the remote
    // service and instead return pre-saved values.
    return function ($handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        $uri = $request->getUri();

        // oEmbed providers.
        if ($uri->__toString() === $this->config->get('oembed_providers_url')) {
          $providers = file_get_contents($this->moduleExtensionList->getPath('oe_media_oembed_mock') . '/responses/providers.json');
          $response = new Response(200, [], $providers);
          return new FulfilledPromise($response);
        }

        // Getting oEmbed json from fixtures.
        if (in_array($uri->getHost(), $this->allowedProviders)) {
          // Dispatch event to gather the JSON data for responses.
          $event = new OEmbedMockEvent($request);
          // Transfer allowed providers.
          $event->setProviders(array_keys($this->allowedProviders));
          $event = $this->eventDispatcher->dispatch($event, OEmbedMockEvent::OEMBED_MOCK_EVENT);
          // Get provider name from current url of request.
          $provider = array_search($uri->getHost(), $this->allowedProviders);
          $ref = $this->getResourceId($uri, $provider);
          // Return available response from fixtures.
          if (isset($event->getResources()[$provider][$ref])) {
            $response = new Response(200, ['Content-Type' => 'application/json'], $event->getResources()[$provider][$ref]);
            return new FulfilledPromise($response);
          }
          // Return empty response if oembed json in fixtures is not available.
          return new FulfilledPromise(new Response());
        }

        // Getting the thumbnail.
        if (in_array($uri->getHost(), $this->thumbnailHosts)) {
          $thumbnail = file_get_contents($this->moduleExtensionList->getPath('media') . '/images/icons/no-thumbnail.png');
          $response = new Response(200, [], $thumbnail);
          return new FulfilledPromise($response);
        }

        // Otherwise, no intervention. We defer to the handler stack.
        return $handler($request, $options)
          ->then(function (ResponseInterface $response) use ($request) {
            return $response;
          });
      };
    };
  }

  /**
   * Helper function for extracting the resource id from the oEmbed url.
   *
   * @param \Psr\Http\Message\UriInterface $uri
   *   The URI.
   * @param string $provider
   *   The provider.
   *
   * @return null|string
   *   The ID.
   */
  protected function getResourceId(UriInterface $uri, string $provider): ?string {
    $video_id = NULL;
    switch ($provider) {
      case 'youtube':
        // For example:
        // https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=z0NfI2NeDHI
        parse_str(parse_url($uri->__toString(), PHP_URL_QUERY), $parsed);
        parse_str(parse_url($parsed['url'] ?? '', PHP_URL_QUERY), $url);
        $video_id = $url['v'] ?? NULL;

        break;

      case 'vimeo':
        // For example:
        // https://vimeo.com/api/oembed.json?url=https%3A//vimeo.com/76979871
        parse_str(parse_url($uri->__toString(), PHP_URL_QUERY), $parsed);
        $video_id = substr(parse_url($parsed['url'] ?? '', PHP_URL_PATH), 1);

        break;

      case 'dailymotion':
        // For example:
        // https://www.dailymotion.com/services/oembed?url=https://www.dailymotion.com/video/x6pa0tr
        parse_str(parse_url($uri->__toString(), PHP_URL_QUERY), $parsed);
        $video_id = substr(parse_url($parsed['url'] ?? '', PHP_URL_PATH), 7);

        break;
    }

    return $video_id;
  }

}
