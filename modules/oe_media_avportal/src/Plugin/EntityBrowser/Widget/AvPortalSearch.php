<?php

declare(strict_types = 1);

namespace Drupal\oe_media_avportal\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Plugin\EntityBrowser\Widget\View;
use Drupal\media\MediaInterface;

/**
 * A View based Entity Browser widget to show AV Portal resources.
 *
 * Selecting remote resources from AV Portal turns them into AV Portal Media
 * entities.
 *
 * @EntityBrowserWidget(
 *   id = "avportal_search",
 *   label = @Translation("AVPortal Search"),
 *   provider = "views",
 *   description = @Translation("Search in AVPortal."),
 *   auto_select = TRUE
 * )
 */
class AVPortalSearch extends View {

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
      // The selected item (row) is the resource ref.
      // @see AvPortalSelectForm::getRowId().
      $entity = $this->getMediaEntityFromRef($row);
      if ($entity) {
        $entities[] = $entity;
      }
    }

    return $entities;
  }

  /**
   * Loads or creates a Media entity for a given resource.
   *
   * @param string $ref
   *   The resource ref.
   *
   * @return \Drupal\media\MediaInterface|null
   *   The media entity.
   */
  protected function getMediaEntityFromRef(string $ref): MediaInterface {
    $bundle = NULL;
    $field = NULL;

    if (preg_match('/I\-(\d+)/', $ref)) {
      $bundle = 'av_portal_video';
      $field = 'oe_media_avportal_video';
    }
    elseif (preg_match('/P\-(\d+)\/(\d+)\-(\d+)/', $ref)) {
      $bundle = 'av_portal_photo';
      $field = 'oe_media_avportal_photo';
    }

    if (!$field || !$bundle) {
      return NULL;
    }

    $entities = $this->entityTypeManager->getStorage('media')->loadByProperties([
      'bundle' => $bundle,
      $field => $ref,
    ]);

    if ($entities) {
      return reset($entities);
    }

    /** @var \Drupal\media\MediaInterface $entity */
    $entity = $this->entityTypeManager->getStorage('media')->create([
      'bundle' => $bundle,
      $field => $ref,
    ]);

    $entity->save();

    return $entity;
  }

}
