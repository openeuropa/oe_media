<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Plugin\EntityBrowser\Widget\View;
use Drupal\media\MediaInterface;

/**
 * A View based Entity Browser widget to show CircaBC resources.
 *
 * Selecting remote resources from CircaBC turns them into Document Media
 * entities.
 *
 * @EntityBrowserWidget(
 *   id = "circabc_search",
 *   label = @Translation("CircaBC Search"),
 *   provider = "views",
 *   description = @Translation("Search in CircaBC."),
 *   auto_select = TRUE
 * )
 */
class CircaBcSearch extends View {

  /**
   * {@inheritdoc}
   *
   * Normally, the View only loads the entities whose IDs were selected. But
   * since we don't have entities, we need to create them if they don't already
   * exist.
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    $selected_rows = array_values(array_filter($form_state->getUserInput()['entity_browser_select']));
    $entities = [];

    foreach ($selected_rows as $row) {
      $entity = $this->getMediaEntityFromUuid($row);
      if ($entity) {
        $entities[] = $entity;
      }
    }

    return $entities;
  }

  /**
   * Loads or creates a Media entity for a given resource.
   *
   * @param string $uuid
   *   The resource UUID.
   *
   * @return \Drupal\media\MediaInterface|null
   *   The media entity.
   */
  protected function getMediaEntityFromUuid(string $uuid): MediaInterface {
    $entities = $this->entityTypeManager->getStorage('media')->loadByProperties([
      'bundle' => 'document',
      'oe_media_circabc_reference.uuid' => $uuid,
    ]);

    if ($entities) {
      return reset($entities);
    }

    /** @var \Drupal\media\MediaInterface $entity */
    $entity = $this->entityTypeManager->getStorage('media')->create([
      'bundle' => 'document',
      'oe_media_file_type' => 'circabc',
      'oe_media_circabc_reference' => [
        'uuid' => $uuid,
      ],
    ]);

    // The save will trigger the data pull.
    $entity->save();

    return $entity;
  }

}
