<?php

declare(strict_types = 1);

namespace Drupal\oe_media\Plugin\EntityBrowser\Widget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetBase;

/**
 * Entity browser widget linking to the AV Portal service for uploading videos.
 *
 * @EntityBrowserWidget(
 *   id = "oe_media_creation_form",
 *   label = @Translation("Media creation form"),
 *   description = @Translation("Creation form for any media."),
 *   auto_select = FALSE
 * )
 */
class MediaCreationForm extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $aditional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $aditional_widget_parameters);

    $context = $form_state->get('entity_browser');
    $target_bundles = $context['widget_context']['target_bundles'] ?? [];
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('media');
    if ($target_bundles) {
      $bundles = array_intersect_key($bundles, $target_bundles);
    }

    $options = [];
    foreach ($bundles as $bundle => $info) {
      $options[$bundle] = $info['label'];
    }

    $id = Html::getId('media_entity_form');

    $form['media_bundle'] = [
      '#type' => 'select',
      '#title' => 'Bundle',
      '#options' => $options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => $id,
      ],
    ];

    $form['entity_form'] = [
      '#type' => 'container',
      '#id' => $id,
    ];

    // Here we need to use the user input because there may be other ajax
    // requests (such as the one for a file upload) which won't contain the
    // bundle in the form state values.
    $user_input = $form_state->getUserInput();
    $bundle = isset($user_input['media_bundle']) ? $user_input['media_bundle'] : NULL;
    if ($bundle && isset($options[$bundle])) {
      // Pretend to be IEFs submit button.
      $form['#submit'] = [
        [
          'Drupal\inline_entity_form\ElementSubmit',
          'trigger',
        ],
      ];
      $form['actions']['submit']['#ief_submit_trigger'] = TRUE;
      $form['actions']['submit']['#ief_submit_trigger_all'] = TRUE;

      $form['entity_form']['inline_entity_form'] = [
        '#type' => 'inline_entity_form',
        '#op' => 'add',
        '#entity_type' => 'media',
        '#bundle' => $bundle,
        '#form_mode' => 'default',
      ];
    }

    return $form;
  }

  /**
   * Ajax callback to generate the media entity form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response object.
   */
  public function ajaxCallback(array &$form) {
    $element = NestedArray::getValue($form, array_merge([
      $form['#browser_parts']['widget'],
      'entity_form',
    ]));
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    $entity_form = $form[$form['#browser_parts']['widget']]['entity_form'];
    if (!isset($entity_form['inline_entity_form'])) {
      return [];
    }
    return [$entity_form['inline_entity_form']['#entity']];
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getTriggeringElement()['#eb_widget_main_submit'])) {
      $entities = $this->prepareEntities($form, $form_state);
      array_walk(
        $entities,
        function (EntityInterface $entity) {
          $entity->save();
        }
      );
      $this->selectEntities($entities, $form_state);
    }
  }

}
