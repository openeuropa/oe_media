<?php

/**
 * @file
 * Views hook implementations for OpenEuropa Media CircaBC.
 */

declare(strict_types=1);

/**
 * Implements hook_views_data().
 */
function oe_media_circabc_views_data(): array {
  $data = [];

  $data['circabc']['table']['group'] = t('CircaBC');
  $data['circabc']['table']['base'] = [
    'title' => t('CircaBC'),
    'help' => t('CircaBC Endpoint API Query.'),
    'query_id' => 'circabc',
    'field' => 'uuid',
  ];

  $data['circabc']['uuid'] = [
    'title' => t('UUID'),
    'help' => t('The UUID.'),
    'field' => [
      'id' => 'standard',
    ],
  ];

  $data['circabc']['title'] = [
    'title' => t('Title'),
    'help' => t('Title.'),
    'field' => [
      'id' => 'standard',
    ],
  ];

  $data['circabc']['search'] = [
    'title' => t('Fulltext search'),
    'help' => t('Fulltext search'),
    'filter' => [
      'id' => 'circabc_text_search',
    ],
  ];

  $data['circabc']['language'] = [
    'title' => t('Language'),
    'help' => t('Language'),
    'filter' => [
      'id' => 'circabc_language',
    ],
    'field' => [
      'id' => 'circabc_language',
    ],
  ];

  $data['circabc']['interest_group'] = [
    'title' => t('Interest group'),
    'help' => t('Interest group'),
    'filter' => [
      'id' => 'circabc_interest_group',
    ],
  ];

  $data['circabc']['content_owner'] = [
    'title' => t('Content owner'),
    'help' => t('Content owner'),
    'filter' => [
      'id' => 'circabc_content_owner',
    ],
    'field' => [
      'id' => 'circabc_content_owner',
    ],
  ];

  $data['circabc']['resource_type'] = [
    'title' => t('Resource type'),
    'help' => t('Resource type'),
    'filter' => [
      'id' => 'circabc_resource_type',
    ],
    'field' => [
      'id' => 'circabc_resource_type',
    ],
  ];

  $data['circabc']['publication_date'] = [
    'title' => t('Publication date'),
    'help' => t('Publication date'),
    'field' => [
      'id' => 'circabc_publication_date',
    ],
  ];

  $data['circabc']['created'] = [
    'title' => t('Created date'),
    'help' => t('Created date'),
    'field' => [
      'id' => 'circabc_created',
    ],
  ];

  $data['circabc']['changed'] = [
    'title' => t('Modification date'),
    'help' => t('Modification date'),
    'field' => [
      'id' => 'circabc_changed',
    ],
  ];

  $data['circabc']['entity_browser_select'] = [
    'title' => t('Entity browser select form'),
    'help' => t('Form element that lets you use a view as a base to select entities in entity browser.'),
    'field' => [
      'id' => 'circabc_select',
      'real field' => 'uuid',
    ],
  ];

  return $data;
}
