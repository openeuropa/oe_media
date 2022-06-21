<?php

declare(strict_types = 1);

namespace Drupal\oe_media\Plugin\EntityBrowser\Widget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Entity browser widget used for creating media of any type.
 *
 * @EntityBrowserWidget(
 *   id = "oe_media_creation_form",
 *   label = @Translation("Media creation form"),
 *   description = @Translation("Creation form for any media."),
 *   auto_select = FALSE
 * )
 */
class MediaCreationForm extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * MediaCreationForm constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);

    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $aditional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $aditional_widget_parameters);

    // Determine the bundles that the field can reference.
    $context = $form_state->get('entity_browser');
    $target_bundles = $context['widget_context']['target_bundles'] ?? [];
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('media');
    if ($target_bundles) {
      $bundles = array_intersect_key($bundles, $target_bundles);
    }

    // The only media bundles available in the select will be the ones the user
    // can create.
    $media_access_control_handler = $this->entityTypeManager->getAccessControlHandler('media');
    $options = [];
    foreach ($bundles as $bundle => $info) {
      $access = $media_access_control_handler->createAccess($bundle);
      if ($access) {
        $options[$bundle] = $info['label'];
      }
    }

    $id = Html::getId('media_entity_form_wrapper');

    if (empty($options)) {
      $form['no_media_bundles'] = [
        '#type' => 'markup',
        '#markup' => $this->t('You cannot create any of the media bundles referenceable by the current field.'),
      ];

      return $form;
    }
    if (count($options) === 1) {
      $form['entity_form'] = [
        '#type' => 'container',
        '#id' => $id,
        'inline_entity_form' => [
          '#type' => 'inline_entity_form',
          '#op' => 'add',
          '#entity_type' => 'media',
          '#bundle' => array_keys($options)[0],
          '#form_mode' => 'default',
        ],
      ];
      // Pretend to be IEFs submit button.
      $form['#submit'] = [
        ['Drupal\inline_entity_form\ElementSubmit', 'trigger'],
      ];
      $form['actions']['submit']['#ief_submit_trigger'] = TRUE;
      $form['actions']['submit']['#ief_submit_trigger_all'] = TRUE;

      return $form;
    }

    $form['media_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#options' => $options,
      '#required' => TRUE,
      '#executes_submit_callback' => TRUE,
      '#limit_validation_errors' => [['media_bundle']],
      '#submit' => [[$this, 'changeMediaBundle']],
      '#ajax' => [
        'callback' => [$this, 'ajaxUpdateMediaForm'],
        'wrapper' => $id,
      ],
      '#default_value' => $form_state->get('media_bundle'),
      '#empty_option' => $this->t('- Select -'),
      '#empty_value' => '_none',
    ];

    $form['entity_form'] = [
      '#type' => 'container',
      '#id' => $id,
    ];

    $bundle = $form_state->get('media_bundle');
    if ($bundle && isset($options[$bundle])) {
      // Pretend to be IEFs submit button.
      $form['#submit'] = [
        ['Drupal\inline_entity_form\ElementSubmit', 'trigger'],
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
   * Updates the bundle value and flags the form for rebuild.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function changeMediaBundle(array $form, FormStateInterface $form_state) : void {
    $form_state->set('media_bundle', $form_state->getValue('media_bundle'));
    $form_state->setRebuild();
  }

  /**
   * Ajax callback to rebuild the media creation form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element.
   */
  public function ajaxUpdateMediaForm(array &$form, FormStateInterface $form_state): array {
    return NestedArray::getValue($form, [
      $form['#browser_parts']['widget'],
      'entity_form',
    ]);
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
    // @see \Drupal\entity_browser_entity_form\Plugin\EntityBrowser\Widget\EntityForm::submit().
    if (!empty($form_state->getTriggeringElement()['#eb_widget_main_submit'])) {
      $entities = $this->prepareEntities($form, $form_state);
      array_walk($entities, function (EntityInterface $entity) {
        $entity->save();
      });
      $this->selectEntities($entities, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    // We cannot get the field's target bundles, so we need to check the create
    // access on all the available media bundles.
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('media');
    $access_handler = $this->entityTypeManager->getAccessControlHandler('media');
    // The users have access to this widget if they have create access to at
    // least one media bundle.
    $access = AccessResult::neutral();
    foreach ($bundles as $bundle => $info) {
      $create_access = $access_handler->createAccess($bundle, NULL, [], TRUE);
      // Do not merge forbidden results, as it would cause the final result to
      // be forbidden too. Keep only the cacheability information.
      if (!$create_access->isForbidden()) {
        $access = $access->orIf($create_access);
      }
      else {
        $access->addCacheableDependency($create_access);
      }
    }
    return $access;
  }

}
