<?php

/**
 * @file
 * OpenEuropa Media AV Portal module.
 */

declare(strict_types = 1);

/**
 * Implements hook_views_data().
 */
function oe_media_avportal_views_data(): array {
  $data = [];

  $data['avportal']['table']['group'] = t('AV Portal');
  $data['avportal']['table']['base'] = [
    'title' => t('AV Portal'),
    'help' => t('AV Portal Endpoint API Query.'),
    // The plugin ID of the plugin that handles this query.
    'query_id' => 'avportal',
  ];

  $data['avportal']['ref'] = [
    'title' => t('Reference'),
    'help' => t('The remote reference ID.'),
    'field' => [
      'id' => 'standard',
    ],
  ];

  $data['avportal']['title'] = [
    'title' => t('Title'),
    'help' => t('Title.'),
    'field' => [
      'id' => 'standard',
    ],
  ];

  $data['avportal']['thumbnail'] = [
    'title' => t('Thumbnail'),
    'help' => t('Thumbnail.'),
    'field' => [
      'id' => 'avportal_thumbnail',
    ],
  ];

  $data['avportal']['search'] = [
    'title' => t('Fulltext search'),
    'help' => t('Fulltext search'),
    'filter' => [
      'id' => 'avportal_text_search',
    ],
  ];

  $data['avportal']['type'] = [
    'title' => t('Type'),
    'help' => t('Resource type'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'avportal_type_search',
    ],
  ];

  $data['avportal']['entity_browser_select'] = [
    'title' => t('Entity browser select form'),
    'help' => t('Form element that lets you use a view as a base to select entities in entity browser.'),
    'field' => [
      'id' => 'avportal_select',
      'real field' => 'ref',
    ],
  ];

  return $data;
}
