<?php

declare(strict_types = 1);

namespace Drupal\oe_media;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaForm;

/**
 * Handles alterations to the Document media form.
 */
class DocumentMediaFormHandler {

  /**
   * Alters the form to handle the remote and local file fields.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function formAlter(array &$form, FormStateInterface $form_state): void {
    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof MediaForm) {
      // This means we are not on a Media form but on an embedded one, in which
      // case we know we are not translating.
      static::applyVisibilityStates($form);
      return;
    }
    /** @var \Drupal\media\MediaInterface $media */
    $media = $form_state->getFormObject()->getEntity();

    $field_definition = $media->getFieldDefinition('oe_media_file_type');
    $is_translating = $media->language()->getId() !== $media->getUntranslated()->language()->getId();
    if (isset($form['oe_media_file_type']) && !$field_definition->isTranslatable() && $is_translating) {
      // Ensure that if the file type field is not translatable, we do not show
      // it on the translation form.
      $form['oe_media_file_type']['#access'] = FALSE;

      // And if that's the case, we need to ensure that we only show the file
      // field (remote or local) where there is value in in the original media.
      $file_type = $media->getUntranslated()->get('oe_media_file_type')->value;
      $hide_map = [
        'local' => 'oe_media_remote_file',
        'remote' => 'oe_media_file',
      ];

      $to_hide = $hide_map[$file_type];
      if (isset($form[$to_hide])) {
        $form[$to_hide]['#access'] = FALSE;
      }

      return;
    }

    // If we are not a translation form, we can apply #states to hide/show the
    // relevant file field.
    static::applyVisibilityStates($form);
  }

  /**
   * Applies the visibility states to the document media form.
   *
   * @param array $form
   *   The media form.
   */
  protected static function applyVisibilityStates(array &$form): void {
    $parents = $form['#parents'];
    $name = 'oe_media_file_type';
    if ($parents) {
      $first_parent = array_shift($parents);
      $name = $first_parent . '[' . implode('][', array_merge($parents, ['oe_media_file_type'])) . ']';
    }

    // Show file field and set it required if field type is local.
    $form['oe_media_file']['widget'][0]['#states'] = [
      'visible' => [
        'select[name="' . $name . '"]' => ['value' => 'local'],
      ],
      'required' => [
        'select[name="' . $name . '"]' => ['value' => 'local'],
      ],
    ];

    // Show remote file field and set the URL required if field type is remote.
    $form['oe_media_remote_file']['widget'][0]['#states'] = [
      'visible' => [
        'select[name="' . $name . '"]' => ['value' => 'remote'],
      ],
    ];
    $form['oe_media_remote_file']['widget'][0]['uri']['#states'] = [
      'required' => [
        'select[name="' . $name . '"]' => ['value' => 'remote'],
      ],
    ];
  }

}
