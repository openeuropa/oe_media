<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\MediaForm;
use Drupal\media\MediaInterface;
use Drupal\oe_media\DocumentMediaFormHandler as OriginalDocumentMediaFormHandler;

/**
 * Overrides the document media handler from the oe_media module.
 */
class DocumentMediaFormHandler extends OriginalDocumentMediaFormHandler {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    parent::formAlter($form, $form_state);

    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof MediaForm) {
      // This means we are not on a Media form but on an embedded one, in which
      // case we know we are not translating.
      $media = $form['#entity'];
      $this->addCircaBcFormElement($form, $form_state, $media);
      return;
    }
    /** @var \Drupal\media\MediaInterface $media */
    $media = $form_state->getFormObject()->getEntity();

    $is_translating = $media->language()->getId() !== $media->getUntranslated()->language()->getId();
    if (isset($form['oe_media_file_type']) && $is_translating && $media->get('oe_media_file_type')->value === 'circabc') {
      // If we are translating and the document type is CircaBC, we hide all
      // the fields because we don't want users to change anything in Drupal.
      foreach ($this->documentTypesMap as $field_name) {
        $form[$field_name]['#access'] = FALSE;
      }
      $form['oe_media_file_type']['#access'] = FALSE;
      $form['oe_media_circabc_reference']['#access'] = FALSE;
      $form['oe_media_file_type']['widget']['#disabled'] = TRUE;
      $form['name']['widget'][0]['value']['#disabled'] = TRUE;
      return;
    }

    // If we are not a translation form, we can apply #states to hide/show the
    // relevant file field.
    $this->addCircaBcFormElement($form, $form_state, $media);
  }

  /**
   * Adds the form element.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\media\MediaInterface $media
   *   The media.
   *
   * @todo handle also optionally to pull or not translations.
   */
  protected function addCircaBcFormElement(array &$form, FormStateInterface $form_state, MediaInterface $media) {
    $parents = $form['#parents'];
    $name = 'oe_media_file_type';
    if ($parents) {
      $first_parent = array_shift($parents);
      $name = $first_parent . '[' . implode('][', array_merge($parents, ['oe_media_file_type'])) . ']';
    }

    $form['circabc_url'] = [
      '#type' => 'url',
      '#title' => $this->t('The CircaBC URL'),
      '#description' => $this->t('Paste here the URL of the CircaBC document. This will be processed and the UUID of the document itself will be saved. Please try to use the URL of the Pivot document from CircaBC and all the translations will automatically be generated.'),
      '#weight' => $form['oe_media_file_type']['#weight'] + 1,
      '#element_validate' => [[static::class, 'validateCircaBcUrl']],
      '#states' => [
        'visible' => [
          'select[name="' . $name . '"]' => ['value' => 'circabc'],
        ],
      ],
      // We can only reference a document when creating media or when the
      // existing media is not of the type CircaBC (so we allow users to
      // switch from local or remote to CircaBC).
      '#access' => $media->isNew() || $media->get('oe_media_file_type')->value !== 'circabc',
      // In case we are in a IEF context in which we don't yet save the media
      // before reopening the edit form.
      '#default_value' => $form_state->get('oe_media_circa_bc_ief_url') ?? '',
    ];

    // Hide the ID field if we are creating the media. Otherwise, we can see
    // it as disabled.
    $form['oe_media_circabc_reference']['#access'] = !$media->isNew() && $media->get('oe_media_file_type')->value === 'circabc';
    $form['oe_media_circabc_reference']['widget'][0]['uuid']['#disabled'] = !$media->isNew();

    // Disable also the file_type field if the media is not new.
    if (!$media->isNew() && $media->get('oe_media_file_type')->value === 'circabc') {
      $form['oe_media_file_type']['widget']['#disabled'] = TRUE;
      $form['name']['widget'][0]['value']['#disabled'] = TRUE;
    }

    // Hide the name field if the user picks CircaBC because the name will
    // get automatically set.
    $form['name']['#states'] = [
      'invisible' => [
        'select[name="' . $name . '"]' => ['value' => 'circabc'],
      ],
    ];
    $form['name']['widget'][0]['value']['#states'] = [
      'optional' => [
        'select[name="' . $name . '"]' => ['value' => 'circabc'],
      ],
    ];

    if ($media->isNew()) {
      $form['name']['widget'][0]['value']['#value_callback'] = [static::class, 'nameValueCallback'];
    }
  }

  /**
   * Sets a default Name value in case the user picks CircaBC.
   *
   * We need this because the field is mandatory and there is no way to do
   * any processing to set a value before the form validator kicks in and
   * fails because the element is required.
   *
   * @param array $element
   *   The form element.
   * @param string $input
   *   The input value.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return string
   *   The value.
   */
  public static function nameValueCallback(array &$element, string $input, FormStateInterface $form_state) {
    $parents = array_merge($element['#field_parents'], ['oe_media_file_type']);
    $value = NestedArray::getValue($form_state->getUserInput(), $parents, $exists);
    if (!$exists) {
      return $input;
    }

    // This value will be replaced in oe_media_circabc_presave().
    if ($value === 'circabc' && $input == "") {
      return 'CircaBC Document';
    }

    // If it's not a CircaBC document, we just return the regular input which
    // would have been anyway set if there was no value callback.
    return $input;
  }

  /**
   * Validates and stores the Document UUID from the URL.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The form.
   */
  public static function validateCircaBcUrl(&$element, FormStateInterface $form_state, &$complete_form) {
    $url = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);
    // Store the URL in the form state so we can get it back in IEF context.
    $form_state->set('oe_media_circa_bc_ief_url', $url);
    if (!$input_exists || $url == "") {
      // It means we are creating a different type of document or we are
      // saving an existing one.
      $value_parents = $element['#parents'];
      array_pop($value_parents);
      $uuid_value_parents = array_merge($value_parents, ['oe_media_circabc_reference', 0, 'uuid']);
      $uuid = $form_state->getValue($uuid_value_parents);
      if ($uuid) {
        $document = \Drupal::service('oe_media_circabc.client')->getDocumentByUuid($uuid);
        if (!$document) {
          $form_state->setError($element, t('There was a problem retrieving the resource from that URL.'));
          return;
        }
      }
      return;
    }

    $base_url = Settings::get('circabc')['url'];
    if (!str_starts_with($url, $base_url)) {
      // The URL needs to start with the configured base URL.
      $form_state->setError($element, t('Please provide a correct CircaBC URL that points to the environment this site has been configured to connect with.'));
      return;
    }

    try {
      $document = \Drupal::service('oe_media_circabc.client')->getDocumentByUrl($url);
      if (!$document) {
        $form_state->setError($element, t('There was a problem retrieving the resource from that URL.'));
        return;
      }
      $uuid = $document->getUuid();
      $parents = $element['#array_parents'];
      array_pop($parents);
      $uuid_parents = array_merge($parents, ['oe_media_circabc_reference', 'widget', 0, 'uuid']);
      $uuid_element = NestedArray::getValue($complete_form, $uuid_parents);
      $form_state->setValueForElement($uuid_element, $uuid);
    }
    catch (\Exception $exception) {
      $form_state->setError($element, t('There was a problem retrieving the resource from that URL'));
    }
  }

}
