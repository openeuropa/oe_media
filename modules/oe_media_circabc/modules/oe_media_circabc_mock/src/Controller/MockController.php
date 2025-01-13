<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc_mock\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for OpenEuropa Media CircaBC Mock routes.
 */
class MockController extends ControllerBase {

  /**
   * Builds the response for the /files path.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function files(array $query = []) {
    if (!$query) {
      // This can work both as a controller and it can be called in Kernel
      // tests.
      $query = \Drupal::request()->query->all();
    }

    $data = [
      'data' => [],
      'total' => 0,
    ];

    $docs_map = [
      'en' => [
        'e74e3bc0-a639-4e04-a839-3bbd60ed5688',
        '8d634abd-fec1-452a-ae0b-62e4cf080506',
      ],
      'fr' => [
        '5d634abd-fec1-452a-ae0b-62e4cf080506',
      ],
      'pt' => [
        '6d634abd-fec1-452a-ae0b-62e4cf080506',
      ],
    ];

    if (isset($query['language']) && !isset($docs_map[$query['language']])) {
      return new JsonResponse($data);
    }

    if ($query['node'] !== Settings::get('circabc')['category']) {
      // If we are not searching inside the entire category,
      // return only a doc from a specific category.
      $file = file_get_contents(\Drupal::service('extension.path.resolver')->getPath('module', 'oe_media_circabc_mock') . '/fixtures/nodes/004e3bc0-a639-4e04-a839-3bbd60ed5600.json');
      $file_data = json_decode($file, TRUE);
      $data['data'] = [$file_data];
      $data['total'] = 1;

      return new JsonResponse($data);
    }

    $docs = [];
    $language = $query['language'] ?? NULL;
    if ($language) {
      $docs = $docs_map[$language];
    }
    else {
      foreach ($docs_map as $ids) {
        $docs = array_merge($docs, $ids);
      }
    }

    // Load the files.
    $files = [];
    foreach ($docs as $id) {
      $file = file_get_contents(\Drupal::service('extension.path.resolver')->getPath('module', 'oe_media_circabc_mock') . '/fixtures/nodes/' . $id . '.json');
      $file_data = json_decode($file, TRUE);
      $files[] = $file_data;
    }

    // Apply filters.
    if (isset($query['q'])) {
      $keywords = $query['q'];
      $files = array_filter($files, function ($file_data) use ($keywords) {
        $locale = $file_data['properties']['locale'];
        return str_contains($file_data['name'], $keywords) ||  str_contains($file_data['title'][$locale], $keywords);
      });
    }

    // For now, the total is the number of items returned, as we don't have a
    // pager.
    $data['total'] = count($docs);

    // Limit and pager.
    if (isset($query['limit'])) {
      $offset = 0;
      if (isset($query['page'])) {
        $offset = (int) floor(((int) $query['page'] - 1) * (int) $query['limit']);
      }
      $files = array_slice($files, $offset, (int) $query['limit']);
    }

    $data['data'] = $files;

    return new JsonResponse($data);
  }

}
