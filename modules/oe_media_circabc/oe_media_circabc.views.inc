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
  ];

  $data['circabc']['interest_group'] = [
    'title' => t('Interest group'),
    'help' => t('Interest group'),
    'filter' => [
      'id' => 'circabc_interest_group',
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
