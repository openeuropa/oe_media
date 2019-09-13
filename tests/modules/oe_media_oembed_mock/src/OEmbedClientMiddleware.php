<?php

declare(strict_types = 1);

namespace Drupal\oe_media_oembed_mock;

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
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

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
   * OEmbedClientMiddleware constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher) {
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

        // Getting oEmbed json from fixtures.
        if (in_array($uri->getHost(), $this->allowedProviders)) {
          // Dispatch event to gather the JSON data for responses.
          $event = new OEmbedMockEvent($request);
          // Transfer allowed providers.
          $event->setProviders(array_keys($this->allowedProviders));
          $event = $this->eventDispatcher->dispatch(OEmbedMockEvent::OEMBED_MOCK_EVENT, $event);
          // Get provider name from current url of request.
          $provider = array_search($uri->getHost(), $this->allowedProviders);
          $ref = $this->getRef($uri, $provider);
          // Return available response from fixtures.
          if (isset($event->getResources()[$provider][$ref])) {
            $response = new Response(200, ['Content-Type' => 'application/json'], $event->getResources()[$provider][$ref]);
            return new FulfilledPromise($response);
          }
          // Return empty response if oembed json in fixtures is not available.
          return new FulfilledPromise(new Response());
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
   * Helper function for extracting the video id from the oEmbed url.
   */
  protected function getRef(Uri $uri, string $provider): ?string {
    $video_id = NULL;
    switch ($provider) {
      case 'youtube':
        // Try to extract video id from url like:
        // https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=z0NfI2NeDHI
        parse_str(parse_url($uri->__toString(), PHP_URL_QUERY), $parsed);
        parse_str(parse_url($parsed['url'] ?? '', PHP_URL_QUERY), $url);
        $video_id = $url['v'] ?? NULL;

        break;

      case 'vimeo':
        // Try to extract video id from url like:
        // https://vimeo.com/api/oembed.json?url=https%3A//vimeo.com/76979871
        parse_str(parse_url($uri->__toString(), PHP_URL_QUERY), $parsed);
        $video_id = substr(parse_url($parsed['url'] ?? '', PHP_URL_PATH), 1);

        break;

      case 'dailymotion':
        // Try to extract video id from url like:
        // https://www.dailymotion.com/services/oembed?url=https://www.dailymotion.com/video/x6pa0tr
        parse_str(parse_url($uri->__toString(), PHP_URL_QUERY), $parsed);
        $video_id = substr(parse_url($parsed['url'] ?? '', PHP_URL_PATH), 7);

        break;
    }

    return $video_id;
  }

}
