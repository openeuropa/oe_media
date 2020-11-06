<?php

declare(strict_types = 1);

namespace Drupal\oe_media;

use Drupal\Core\Form\FormStateInterface;

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
