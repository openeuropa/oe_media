<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc_mock;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\oe_media_circabc_mock\Controller\MockController;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Mocks the CircaBC responses.
 */
class CircaBcMiddleware {

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * Constructs a new CircaBcMiddleware.
   *
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extensionPathResolver
   *   The extension path resolver.
   */
  public function __construct(ExtensionPathResolver $extensionPathResolver) {
    $this->extensionPathResolver = $extensionPathResolver;
  }

  /**
   * Invoked method that returns a promise.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function __invoke() {
    return function ($handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        $url = (string) $request->getUri();
        $parsed = parse_url($url);
        if (!isset($parsed['path'])) {
          return $handler($request, $options);
        }

        $path = $parsed['path'];
        $test_module_path = $this->extensionPathResolver->getPath('module', 'oe_media_circabc_mock');

        if (str_starts_with($path, '/circabc-ewpp/service/circabc/nodes/')) {
          $uuid = str_replace('/circabc-ewpp/service/circabc/nodes/', '', $path);
          $filename = $test_module_path . '/fixtures/nodes/' . $uuid . '.json';
          if (file_exists($filename)) {
            $response = new Response(200, [], file_get_contents($filename));

            return new FulfilledPromise($response);
          }
        }

        if (str_starts_with($path, '/circabc-ewpp/service/circabc/content/') && str_ends_with($path, '/translations')) {
          $uuid = str_replace('/circabc-ewpp/service/circabc/content/', '', $path);
          $uuid = str_replace('/translations', '', $uuid);
          $filename = $test_module_path . '/fixtures/translations/' . $uuid . '.json';
          if (file_exists($filename)) {
            $response = new Response(200, [], file_get_contents($filename));

            return new FulfilledPromise($response);
          }
        }

        if (str_starts_with($path, '/circabc-ewpp/service/circabc/categories/') && str_ends_with($path, '/groups')) {
          $filename = $test_module_path . '/fixtures/interest_groups.json';
          if (file_exists($filename)) {
            $response = new Response(200, [], file_get_contents($filename));

            return new FulfilledPromise($response);
          }
        }

        if ($path === '/circabc-ewpp/service/circabc/files') {
          $response = (new MockController())->files(UrlHelper::parse($url)['query']);
          $response = new Response(200, [], $response->getContent());
          return new FulfilledPromise($response);
        }

        // Otherwise, no intervention. We defer to the handler stack.
        return $handler($request, $options);
      };
    };
  }

}
