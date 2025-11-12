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

        // The document upload endpoint.
        if ($request->getMethod() === 'POST' && str_starts_with($path, '/circabc-ewpp/service/circabc/nodes/') && str_ends_with($path, '/upload')) {
          $folder_uuid = str_replace('/circabc-ewpp/service/circabc/nodes/', '', $path);
          $folder_uuid = str_replace('/upload', '', $folder_uuid);
          // If we don't have the interest group, return a 500 response.
          $interest_groups = $this->getInterestGroups();
          $interest_group = NULL;
          foreach ($interest_groups as $group) {
            if ($folder_uuid == $group->libraryId) {
              $interest_group = $group;
              break;
            }
          }
          if (!$interest_group) {
            $response = new Response(500, [], 'The interest group is missing');
            return new FulfilledPromise($response);
          }

          // Log the request.
          $requests = \Drupal::state()->get('oe_media_circabc_mock_requests', []);
          $data = $this->getRequestData($request);
          $requests[$path][] = $data;
          $request->getBody()->rewind();
          \Drupal::state()->set('oe_media_circabc_mock_requests', $requests);

          // Mock the creation of the document in CircaBC by returning a
          // nodeRef.
          $documents = \Drupal::state()->get('oe_media_circabc_mock_uploaded_documents', []);
          $uuid = \Drupal::service('uuid')->generate();
          $data['translations'] = [];
          $documents[$uuid] = $data;
          \Drupal::state()->set('oe_media_circabc_mock_uploaded_documents', $documents);

          $response = new Response(200, [], json_encode([
            'nodeRef' => $uuid,
          ]));
          return new FulfilledPromise($response);
        }

        // The document translation upload endpoint.
        if ($request->getMethod() === 'POST' &&  str_starts_with($path, '/circabc-ewpp/service/circabc/content/') && str_ends_with($path, '/translations/enhanced')) {
          $uuid = str_replace('/circabc-ewpp/service/circabc/content/', '', $path);
          $uuid = str_replace('/translations/enhanced', '', $uuid);
          $documents = \Drupal::state()->get('oe_media_circabc_mock_uploaded_documents', []);

          // Log the request.
          $requests = \Drupal::state()->get('oe_media_circabc_mock_requests', []);
          $data = $this->getRequestData($request);
          $requests[$path][] = $data;
          $request->getBody()->rewind();
          \Drupal::state()->set('oe_media_circabc_mock_requests', $requests);

          // If we don't have the pivot, return a 500 response.
          if (!isset($documents[$uuid])) {
            $response = new Response(500, [], 'The pivot is missing');
            return new FulfilledPromise($response);
          }

          // Mock the creation of the translation in CircaBC.
          $document = &$documents[$uuid];
          $translation_uuid = \Drupal::service('uuid')->generate();
          $data['id'] = $translation_uuid;
          $document['translations'][$data['lang']] = $data;
          \Drupal::state()->set('oe_media_circabc_mock_uploaded_documents', $documents);

          $response = new Response(200, [], json_encode([
            'nodeRef' => $translation_uuid,
          ]));
          return new FulfilledPromise($response);
        }

        // The document delete endpoint.
        if ($request->getMethod() === 'DELETE' && str_starts_with($path, '/circabc-ewpp/service/circabc/content/')) {
          $uuid = str_replace('/circabc-ewpp/service/circabc/content/', '', $path);

          // Log the request.
          $requests = \Drupal::state()->get('oe_media_circabc_mock_requests', []);
          $data = $this->getRequestData($request);
          $requests[$path] = $data;
          $request->getBody()->rewind();
          \Drupal::state()->set('oe_media_circabc_mock_requests', $requests);

          $documents = \Drupal::state()->get('oe_media_circabc_mock_uploaded_documents', []);

          // If we don't have the pivot, return a 500 response.
          if (!isset($documents[$uuid])) {
            $response = new Response(500, [], 'The document is missing');
            return new FulfilledPromise($response);
          }

          unset($documents[$uuid]);

          \Drupal::state()->set('oe_media_circabc_mock_uploaded_documents', $documents);
          $response = new Response(200, [], json_encode([
            'data' => 'deleted',
          ]));

          return new FulfilledPromise($response);
        }

        // The nodes (regular document retrieval) endpoint.
        if (str_starts_with($path, '/circabc-ewpp/service/circabc/nodes/')) {
          $uuid = str_replace('/circabc-ewpp/service/circabc/nodes/', '', $path);
          $filename = $test_module_path . '/fixtures/nodes/' . $uuid . '.json';
          if (file_exists($filename)) {
            $response = new Response(200, [], file_get_contents($filename));

            return new FulfilledPromise($response);
          }
        }

        // The nodes (regular document retrieval) endpoint.
        if (str_starts_with($path, '/circabc-ewpp/service/circabc/nodes/')) {
          $uuid = str_replace('/circabc-ewpp/service/circabc/nodes/', '', $path);
          $filename = $test_module_path . '/fixtures/nodes/' . $uuid . '.json';
          // If the file exists in the regular export, use that.
          if (file_exists($filename)) {
            $response = new Response(200, [], file_get_contents($filename));

            return new FulfilledPromise($response);
          }

          // Otherwise, try it in the state as it may have been freshly
          // uploaded.
          $documents = \Drupal::state()->get('oe_media_circabc_mock_uploaded_documents', []);
          if (isset($documents[$uuid])) {
            $upload = json_decode(file_get_contents($test_module_path . '/fixtures/upload.json'), TRUE);
            $this->fillUploadTemplate($upload, $documents[$uuid], $uuid);
            $response = new Response(200, [], json_encode($upload));

            return new FulfilledPromise($response);
          }
        }

        // The document translations endpoint.
        if (str_starts_with($path, '/circabc-ewpp/service/circabc/content/') && str_ends_with($path, '/translations')) {
          $uuid = str_replace('/circabc-ewpp/service/circabc/content/', '', $path);
          $uuid = str_replace('/translations', '', $uuid);
          $filename = $test_module_path . '/fixtures/translations/' . $uuid . '.json';
          if (file_exists($filename)) {
            $response = new Response(200, [], file_get_contents($filename));

            return new FulfilledPromise($response);
          }

          // Otherwise, try it in the state as it may have been freshly
          // uploaded.
          $documents = \Drupal::state()->get('oe_media_circabc_mock_uploaded_documents', []);
          if (isset($documents[$uuid])) {
            $upload = json_decode(file_get_contents($test_module_path . '/fixtures/upload_translations.json'), TRUE);
            $this->fillUploadTemplate($upload, $documents[$uuid], $uuid, TRUE);
            $response = new Response(200, [], json_encode($upload));

            return new FulfilledPromise($response);
          }
        }

        // The interest groups endpoint.
        if (str_starts_with($path, '/circabc-ewpp/service/circabc/categories/') && str_ends_with($path, '/groups')) {
          $filename = $test_module_path . '/fixtures/interest_groups.json';
          if (file_exists($filename)) {
            $response = new Response(200, [], file_get_contents($filename));

            return new FulfilledPromise($response);
          }
        }

        // The individual group endpoint.
        if (str_starts_with($path, '/circabc-ewpp/service/circabc/groups/')) {
          $uuid = str_replace('/circabc-ewpp/service/circabc/groups/', '', $path);
          $filename = $test_module_path . '/fixtures/interest_groups/' . $uuid . '.json';
          if (file_exists($filename)) {
            $response = new Response(200, [], file_get_contents($filename));

            return new FulfilledPromise($response);
          }
        }

        // The query endpoint.
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

  /**
   * Returns the fixtures interest groups.
   *
   * @return array
   *   The interest groups.
   */
  protected function getInterestGroups(): array {
    $test_module_path = $this->extensionPathResolver->getPath('module', 'oe_media_circabc_mock');
    $interest_groups = [];
    foreach (scandir($test_module_path . '/fixtures/interest_groups') as $file) {
      if (!str_ends_with($file, '.json')) {
        continue;
      }

      $uuid = str_replace('.json', '', $file);
      $interest_groups[$uuid] = json_decode(file_get_contents($test_module_path . '/fixtures/interest_groups/' . $file));
    }

    return $interest_groups;
  }

  /**
   * Returns the request boundary.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request.
   *
   * @return string
   *   The boundary.
   */
  protected function getRequestBoundary(RequestInterface $request): ?string {
    preg_match('/boundary=(?P<boundary>[a-zA-Z0-9\'()+_\-,\.\/:=\?]+)/', $request->getHeaderLine('Content-Type'), $found);
    return $found[1] ?? NULL;
  }

  /**
   * Gets multipart stream resources if present.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request.
   * @param string $boundary
   *   The boundary.
   *
   * @return false|string[]
   *   Multipart stream resources if present.
   */
  protected function getRequestMultipartStreamResources(RequestInterface $request, string $boundary) {
    $parts = explode("--{$boundary}", $request->getBody()->getContents());
    // The first and last entries are empty.
    // @todo Improve this.
    array_shift($parts);
    array_pop($parts);

    return $parts;
  }

  /**
   * Gets the request data.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request.
   *
   * @return array
   *   The data.
   */
  protected function getRequestData(RequestInterface $request): array {
    $boundary = $this->getRequestBoundary($request);
    if (!$boundary) {
      return [];
    }
    $resources = $this->getRequestMultipartStreamResources($request, $boundary);
    $data = [];
    foreach ($resources as $resource) {
      $parts = explode("\r\n", $resource);
      $parts = array_values(array_filter($parts));
      $exploded = explode('; ', $parts[0]);
      preg_match('/name="(?P<name>[^"]+)"/', $exploded[1], $found);
      $field_name = $found[1];
      if (in_array($field_name, ['fileName', 'file'])) {
        // We don't care about the stream.
        $value = NULL;
      }
      else {
        $value = $parts[2];
      }

      $data[$field_name] = $value;
    }

    return $data;
  }

  /**
   * Fulls the upload template with document data from the state.
   *
   * @param array $upload
   *   The upload template.
   * @param array $document
   *   The document data.
   * @param string $uuid
   *   The document UUID.
   * @param bool $translations
   *   Whether to include translations.
   */
  protected function fillUploadTemplate(array &$upload, array $document, string $uuid, bool $translations = FALSE): void {
    if ($translations) {
      $pivot = &$upload['pivot'];
    }
    else {
      $pivot = &$upload;
    }
    $language = $document['lang'];
    $pivot['id'] = $uuid;
    $pivot['name'] = $document['name'];
    $pivot['properties']['node-uuid'] = $uuid;
    $pivot['properties']['name'] = $document['name'];
    $pivot['title'][$language] = json_decode($document['title'], TRUE)[$language];
    $pivot['description'][$language] = json_decode($document['description'], TRUE)[$language];

    if (isset($document['subject'])) {
      $pivot['properties']['subject'] = "[{$document['subject']}]";
    }
    if (isset($document['contentOwner'])) {
      $pivot['properties']['contentOwner'] = "[{$document['contentOwner']}]";
    }
    if (isset($document['resourceType'])) {
      $pivot['properties']['resourceType'] = $document['resourceType'];
    }
    if (isset($document['publicationDate'])) {
      $pivot['properties']['issue_date'] = $document['publicationDate'];
    }

    if ($translations) {
      $translation_template = $upload['translations'][0];
      $upload['translations'] = [];
      foreach ($document['translations'] as $langcode => $translation_data) {
        $translation = $translation_template;
        $translation['id'] = $translation_data['id'];
        $translation['name'] = $translation_data['name'];
        $translation['properties']['locale'] = $langcode;
        $translation['properties']['node-uuid'] = $translation_data['id'];
        $translation['properties']['name'] = $translation_data['name'];
        $translation['title'][$langcode] = json_decode($translation_data['title'], TRUE)[$langcode];
        $translation['description'][$langcode] = json_decode($translation_data['description'], TRUE)[$langcode];
        $upload['translations'][] = $translation;
      }
    }
  }

}
