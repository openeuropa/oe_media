<?php

declare(strict_types = 1);

namespace Drupal\oe_media_oembed_mock;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A Guzzle middleware for testing the oEmbed medias.
 *
 * This is not intended for production use.
 */
class OEmbedClientMiddleware {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  protected $allowedProviders = [
    'youtube' => 'www.youtube.com',
    'vimeo' => 'vimeo.com',
    'dailymotion' => 'www.dailymotion.com',

  ];

  /**
   * AvPortalClientMiddleware constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EventDispatcherInterface $eventDispatcher) {
    $this->configFactory = $configFactory;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * HTTP middleware that returns pre-saved data for AV Portal requests.
   */
  public function __invoke() {
    // For oEmbed requests, we need to skip the execution to the remote
    // service and instead return pre-saved values.
    return function ($handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        $uri = $request->getUri();

        // oEmbed providers.
        if ($uri->__toString() === 'https://oembed.com/providers.json') {
          $providers = file_get_contents(drupal_get_path('module', 'oe_media_oembed_mock') . '/responses/providers.json');
          $response = new Response(200, [], $providers);
          return new FulfilledPromise($response);
        }

        // oEmbed providers.
        if (in_array($uri->getHost(), $this->allowedProviders)) {
          // Dispatch event to gather the JSON data for responses.
          $event = new OEmbedMockEvent($request);
          $event->setProviders(array_keys($this->allowedProviders));
          $event = $this->eventDispatcher->dispatch(OEmbedMockEvent::OEMBED_MOCK_EVENT, $event);
          $provider = array_search($uri->getHost(), $this->allowedProviders);
          $ref = $this->getRef($uri, $provider);
          if (isset($event->getResources()[$provider][$ref])) {
            $response = new Response(200, ['Content-Type' => 'application/json'], $event->getResources()[$provider][$ref]);
            return new FulfilledPromise($response);
          }
        }


//
//        // AV Portal thumbnails.
//        if ($uri->getHost() === 'defiris.ec.streamcloud.be' || strpos($uri->getPath(), 'avservices/avs/files/video6/repository/prod/photo/store/')) {
//          $thumbnail = file_get_contents(drupal_get_path('module', 'media') . '/images/icons/no-thumbnail.png');
//          $response = new Response(200, [], $thumbnail);
//          return new FulfilledPromise($response);
//        }

        // Otherwise, no intervention. We defer to the handler stack.
        return $handler($request, $options)
          ->then(function (ResponseInterface $response) use ($request) {
            return $response;
          });
      };
    };
  }


  protected function getRef(Uri $uri, string $provider): ?string {
    switch ($provider) {
      case 'youtube':
        // Try to extract video id from url like:
        // https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=z0NfI2NeDHI
        parse_str(parse_url($uri->__toString(), PHP_URL_QUERY), $parsed);
        parse_str(parse_url($parsed['url'] ?? '', PHP_URL_QUERY), $url);
        return $url['v'] ?? NULL;

        break;
      case 'vimeo':
        parse_str(parse_url($uri->__toString(), PHP_URL_QUERY), $parsed);
        return substr(parse_url($parsed['url'] ?? '', PHP_URL_PATH), 1);

        break;
      case 'dailymotion':
        parse_str(parse_url($uri->__toString(), PHP_URL_QUERY), $parsed);
        return substr(parse_url($parsed['url'] ?? '', PHP_URL_PATH), 7);

        break;
    }
  }

//  /**
//   * Creates responses from pre-saved JSON data.
//   *
//   * @param \Psr\Http\Message\RequestInterface $request
//   *   The Guzzle request.
//   *
//   * @return \GuzzleHttp\Promise\PromiseInterface
//   *   The Guzzle promise.
//   */
//  protected function createServicePromise(RequestInterface $request): PromiseInterface {
//    // Dispatch event to gather the JSON data for responses.
//    $event = new AvPortalMockEvent($request);
//    $event = $this->eventDispatcher->dispatch(AvPortalMockEvent::AV_PORTAL_MOCK_EVENT, $event);
//
//    $uri = $request->getUri();
//    $query = $uri->getQuery();
//    $params = [];
//    parse_str($query, $params);
//
//    // Replace | with / .
//    if (isset($params['ref'])) {
//      $params['ref'] = str_replace('/', '|', $params['ref']);
//      // It means we are requesting a particular resource.
//      return $this->createIndividualResourcePromise($event->getResources(), $params['ref']);
//    }
//
//    $resource_type = 'all';
//    if (!empty($params['type']) && $params['type'] == 'VIDEO') {
//      $resource_type = 'video';
//    }
//    elseif (!empty($params['type']) && $params['type'] == 'PHOTO') {
//      $resource_type = 'photo';
//    }
//
//    // If we are searching, we need to look at some search responses.
//    if (isset($params['kwgg'])) {
//      $searches = $event->getSearches();
//      $json = isset($searches[$resource_type . '-' . $params['kwgg']]) ? $searches[$resource_type . '-' . $params['kwgg']] : $searches[$resource_type . '-empty'];
//    }
//    else {
//      // Otherwise, we default to the regular response.
//      $json = $event->getDefault($resource_type);
//    }
//
//    return $this->createPaginatedJsonPromise($json, $params);
//  }

  /**
   * Handles the case of a request to a single resource.
   *
   * @param array $resources
   *   Mocked and available resources.
   * @param string $ref
   *   The resource reference.
   *
   * @return \GuzzleHttp\Promise\FulfilledPromise
   *   The middleware promise.
   */
//  protected function createIndividualResourcePromise(array $resources, string $ref): PromiseInterface {
//    if (isset($resources[$ref])) {
//      $resource = $resources[$ref];
//      $response = new Response(200, [], $resource);
//      return new FulfilledPromise($response);
//    }
//
//    // If our ref is not mocked, we consider it as a not found resource.
//    $resource = $resources['not-found'];
//    $response = new Response(200, [], $resource);
//    return new FulfilledPromise($response);
//  }

  /**
   * Creates a paginated JSON response from an existing mocked JSON response.
   *
   * Responses with multiple resources can be paginated so this method takes
   * care of the mocked responses to return only the relevant items.
   *
   * @param string $json
   *   The mocked JSON response.
   * @param array $params
   *   The request parameters that contain the pagination info.
   *
   * @return \GuzzleHttp\Promise\FulfilledPromise
   *   The middleware promise.
   */
//  protected function createPaginatedJsonPromise(string $json, array $params): PromiseInterface {
//    // For both default and search query, we need to account for pagination.
//    $decoded = json_decode($json);
//    // Index starts with 1 in AV Portal so we need to subtract 1.
//    $index = (int) $params['index'] - 1;
//    $length = (int) $params['pagesize'];
//    $docs = array_slice($decoded->response->docs, $index, $length);
//    $decoded->response->docs = $docs;
//    $decoded->responseHeader->params->index = $params['index'];
//    $decoded->responseHeader->params->pagesize = $params['pagesize'];
//    $json = json_encode($decoded);
//
//    $response = new Response(200, [], $json);
//    return new FulfilledPromise($response);
//  }

}
