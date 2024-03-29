<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\Traits;

use Drupal\media\Entity\MediaType;

/**
 * Provides methods to create a media type from given values.
 *
 * This trait is a copy of the media module one, with some differences:
 * - saves the media type after creation, to avoid errors during dependency
 *   calculation.
 * - updates the source configuration stored inside the media type to save the
 *   field name.
 * - prepares the view display.
 *
 * @see \Drupal\Tests\media\Traits\MediaTypeCreationTrait
 */
trait MediaTypeCreationTrait {

  /**
   * Create a media type for a source plugin.
   *
   * @param string $source_plugin_id
   *   The media source plugin ID.
   * @param mixed[] $values
   *   (optional) Additional values for the media type entity:
   *   - id: The ID of the media type. If none is provided, a random value will
   *     be used.
   *   - label: The human-readable label of the media type. If none is provided,
   *     a random value will be used.
   *   - bundle: (deprecated) The ID of the media type, for backwards
   *     compatibility purposes. Use 'id' instead.
   *   See \Drupal\media\MediaTypeInterface and \Drupal\media\Entity\MediaType
   *   for full documentation of the media type properties.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   A media type.
   *
   * @see \Drupal\media\MediaTypeInterface
   * @see \Drupal\media\Entity\MediaType
   */
  protected function createMediaType($source_plugin_id, array $values = []) {
    if (isset($values['bundle'])) {
      @trigger_error('Setting the "bundle" key when creating a test media type is deprecated in drupal:8.6.0 and will be removed before drupal:9.0.0. Set the "id" key instead. See https://www.drupal.org/node/2981614', E_USER_DEPRECATED);
      $values['id'] = $values['bundle'];
      unset($values['bundle']);
    }

    $values += [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'source' => $source_plugin_id,
    ];

    /** @var \Drupal\media\MediaTypeInterface $media_type */
    $media_type = MediaType::create($values);
    $this->assertSame(SAVED_NEW, $media_type->save());

    $source = $media_type->getSource();
    $source_field = $source->createSourceField($media_type);
    $source_configuration = $source->getConfiguration();
    $source_configuration['source_field'] = $source_field->getName();
    $source->setConfiguration($source_configuration);
    // Update the source configuration stored in the media type.
    $media_type->set('source_configuration', $source->getConfiguration());
    $media_type->save();

    // The media type form creates a source field if it does not exist yet. The
    // same must be done in a kernel test, since it does not use that form.
    // @see \Drupal\media\MediaTypeForm::save()
    $source_field->getFieldStorageDefinition()->save();
    // The source field storage has been created, now the field can be saved.
    $source_field->save();

    // Add the source field to the form display for the media type.
    $form_display = \Drupal::service('entity_display.repository')->getFormDisplay('media', $media_type->id(), 'default');
    $source->prepareFormDisplay($media_type, $form_display);
    $form_display->save();

    // Do the same for the view display.
    $view_display = \Drupal::service('entity_display.repository')->getViewDisplay('media', $media_type->id());
    $source->prepareViewDisplay($media_type, $view_display);
    $view_display->save();

    return $media_type;
  }

}
