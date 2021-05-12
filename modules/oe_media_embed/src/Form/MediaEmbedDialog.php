<?php

declare(strict_types = 1);

namespace Drupal\oe_media_embed\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\SetDialogTitleCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\editor\EditorInterface;
use Drupal\embed\EmbedButtonInterface;
use Drupal\entity_browser\Events\Events;
use Drupal\entity_browser\Events\RegisterJSCallbacks;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a form to embed media by specifying data attributes.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class MediaEmbedDialog extends FormBase {

  /**
   * The entity browser.
   *
   * @var \Drupal\entity_browser\EntityBrowserInterface
   */
  protected $entityBrowser;

  /**
   * The entity browser settings from the entity embed button.
   *
   * @var array
   */
  protected $entityBrowserSettings = [];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a MediaEmbedDialog object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The Form Builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Module handler service.
   */
  public function __construct(FormBuilderInterface $form_builder, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, ModuleHandler $module_handler) {
    $this->formBuilder = $form_builder;
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_embed_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function buildForm(array $form, FormStateInterface $form_state, EditorInterface $editor = NULL, EmbedButtonInterface $embed_button = NULL): array {
    $values = $form_state->getValues();
    $input = $form_state->getUserInput();
    // Set embed button element in form state, so that it can be used later in
    // validateForm() function.
    $form_state->set('embed_button', $embed_button);
    $form_state->set('editor', $editor);
    // Initialize entity element with form attributes, if present.
    $entity_element = empty($values['attributes']) ? [] : $values['attributes'];
    $entity_element += empty($input['attributes']) ? [] : $input['attributes'];
    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    if (!$form_state->get('entity_element')) {
      $form_state->set('entity_element', isset($input['editor_object']) ? $input['editor_object'] : []);
    }
    $entity_element += $form_state->get('entity_element');
    $entity_element += [
      'data-entity-uuid' => '',
    ];
    $form_state->set('entity_element', $entity_element);
    $uuid = $entity_element['data-entity-uuid'];
    $entity = $uuid ? $this->entityTypeManager->getStorage('media')->loadByProperties(['uuid' => $uuid]) : [];
    $form_state->set('entity', current($entity) ?: NULL);

    if (!$form_state->get('step')) {
      // If an entity has been selected, then always skip to the embed options.
      if ($form_state->get('entity')) {
        $form_state->set('step', 'embed');
      }
      else {
        $form_state->set('step', 'select');
      }
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#attached']['library'][] = 'oe_media_embed/media_embed.dialog';
    $form['#prefix'] = '<div id="media-embed-dialog-form">';
    $form['#suffix'] = '</div>';
    $form['#attributes']['class'][] = 'media-embed-dialog-step--' . $form_state->get('step');

    $this->loadEntityBrowser($form_state);

    if ($form_state->get('step') == 'select') {
      $form = $this->buildSelectStep($form, $form_state);
    }
    elseif ($form_state->get('step') == 'review') {
      $form = $this->buildReviewStep($form, $form_state);
    }
    elseif ($form_state->get('step') == 'embed') {
      $form = $this->buildEmbedStep($form, $form_state);
    }

    return $form;
  }

  /**
   * Builds the form of the selection step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildSelectStep(array &$form, FormStateInterface $form_state): array {
    // Entity element is calculated on every AJAX request/submit.
    // See self::buildForm().
    $entity_element = $form_state->get('entity_element');
    $entity = $form_state->get('entity');
    /** @var \Drupal\embed\EmbedButtonInterface $embed_button */
    $embed_button = $form_state->get('embed_button');

    $label = $this->t('Label');
    $form['#title'] = $this->t('Select Media to embed');

    if ($this->entityBrowser) {
      $this->eventDispatcher->addListener(Events::REGISTER_JS_CALLBACKS, [$this, 'registerJsCallback']);
      $form['entity_browser'] = [
        '#type' => 'entity_browser',
        '#entity_browser' => $this->entityBrowser->id(),
        '#cardinality' => 1,
        '#entity_browser_validators' => [
          'entity_type' => ['type' => 'media'],
        ],
      ];
    }
    else {
      $media_types = $embed_button->getTypeSetting('media_types');
      $form['entity_id'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'media',
        '#selection_settings' => [
          'target_bundles' => array_values($media_types),
        ],
        '#title' => $label,
        '#default_value' => $entity,
        '#required' => TRUE,
        '#description' => $this->t('Type label and pick the right one from suggestions. Note that the unique ID will be saved.'),
        '#maxlength' => 255,
      ];
    }

    $form['attributes']['data-entity-uuid'] = [
      '#type' => 'value',
      '#title' => $entity_element['data-entity-uuid'],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#button_type' => 'primary',
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitSelectStep',
        'event' => 'click',
      ],
      '#attributes' => [
        'class' => [
          'js-button-next',
        ],
      ],
    ];

    return $form;
  }

  /**
   * Builds the form of the review step.
   *
   * This is only used when using an entity browser.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildReviewStep(array &$form, FormStateInterface $form_state): array {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_state->get('entity');

    $form['#title'] = $this->t('Review selected @type', ['@type' => $entity->getEntityType()->getSingularLabel()]);

    $form['selection'] = [
      '#markup' => $entity->label(),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Replace selection'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitAndShowSelect',
        'event' => 'click',
      ],
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#button_type' => 'primary',
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitAndShowEmbed',
        'event' => 'click',
      ],
      '#attributes' => [
        'class' => [
          'js-button-next',
        ],
      ],
    ];

    return $form;
  }

  /**
   * Builds the form of the embed step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildEmbedStep(array $form, FormStateInterface $form_state): array {
    // Entity element is calculated on every AJAX request/submit.
    // See self::buildForm().
    $entity_element = $form_state->get('entity_element');
    /** @var \Drupal\embed\EmbedButtonInterface $embed_button */
    $embed_button = $form_state->get('embed_button');
    /** @var \Drupal\media\MediaInterface $entity */
    $entity = $form_state->get('entity');

    $form['#title'] = $this->t('Embed media');

    $form['entity'] = [
      '#type' => 'item',
      '#title' => $this->t('Selected entity'),
      '#markup' => $entity->toLink(NULL, 'canonical', ['attributes' => ['target' => '_blank']])->toString(),
    ];

    $form['attributes']['data-embed-button'] = [
      '#type' => 'value',
      '#value' => $embed_button->id(),
    ];

    $form['attributes']['data-entity-uuid'] = [
      '#type' => 'hidden',
      '#value' => $entity_element['data-entity-uuid'],
    ];

    $form['attributes'] += $this->getMediaViewModeFormElement($form_state->get('entity_element'), $form_state->get('entity'));

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => !empty($this->entityBrowserSettings['display_review']) ? '::submitAndShowReview' : '::submitAndShowSelect',
        'event' => 'click',
      ],
    ];

    // Only show the submit button if a view mode is available.
    if (isset($form['attributes']['data-entity-view-mode'])) {
      $form['actions']['save_modal'] = [
        '#type' => 'submit',
        '#value' => $this->t('Embed'),
        '#button_type' => 'primary',
        // No regular submit-handler. This form only works via JavaScript.
        '#submit' => [],
        '#ajax' => [
          'callback' => '::submitEmbedStep',
          'event' => 'click',
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // We only need validation on the selection step.
    if ($form_state->get('step') == 'select') {
      $this->validateSelectStep($form, $form_state);
    }
  }

  /**
   * Form validation handler for the selection step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateSelectStep(array $form, FormStateInterface $form_state): void {
    if (($form_state->hasValue(['entity_browser', 'entities'])) && (count($form_state->getValue(['entity_browser', 'entities'])) > 0)) {
      $id = $form_state->getValue(['entity_browser', 'entities', 0])->id();
      $element = $form['entity_browser'];
    }
    else {
      $id = trim($form_state->getValue(['entity_id']));
      $element = $form['entity_id'];
    }

    $entity = $this->entityTypeManager->getStorage('media')->load($id);
    if (!$entity instanceof MediaInterface) {
      $form_state->setError($element, $this->t('Unable to load Media entity @id.', ['@id' => $id]));
      return;
    }

    if (!$entity->access('view')) {
      $form_state->setError($element, $this->t('Unable to access Media entity @id.', ['@id' => $id]));
      return;
    }

    $uuid = $entity->uuid();
    if ($uuid) {
      $form_state->setValueForElement($form['attributes']['data-entity-uuid'], $uuid);
      return;
    }

    $form_state->setError($element, $this->t('Cannot embed Media entity @id because it does not have a UUID.', ['@id' => $id]));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

  /**
   * Form submission handler for another step of the form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $step
   *   The next step name, such as 'select', 'review' or 'embed'.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function submitStep(array &$form, FormStateInterface $form_state, $step): AjaxResponse {
    $response = new AjaxResponse();

    $form_state->set('step', $step);
    $form_state->setRebuild(TRUE);
    $rebuild_form = $this->formBuilder->rebuildForm('media_embed_dialog', $form_state, $form);
    unset($rebuild_form['#prefix'], $rebuild_form['#suffix']);
    $response->addCommand(new HtmlCommand('#media-embed-dialog-form', $rebuild_form));
    $response->addCommand(new SetDialogTitleCommand('', $rebuild_form['#title']));

    return $response;
  }

  /**
   * Form submission handler for the selection step.
   *
   * On success will send the user to the next step of the form to select the
   * embed display settings (if any). On form errors, this will rebuild the form
   * and display the error messages.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function submitSelectStep(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    // Display errors in form, if any.
    if ($form_state->hasAnyErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#media-embed-dialog-form', $form));

      return $response;
    }

    $form_state->set('step', !empty($this->entityBrowserSettings['display_review']) ? 'review' : 'embed');
    $form_state->setRebuild(TRUE);
    $rebuild_form = $this->formBuilder->rebuildForm('media_embed_dialog', $form_state, $form);
    unset($rebuild_form['#prefix'], $rebuild_form['#suffix']);
    $response->addCommand(new HtmlCommand('#media-embed-dialog-form', $rebuild_form));
    $response->addCommand(new SetDialogTitleCommand('', $rebuild_form['#title']));

    return $response;
  }

  /**
   * Submit and show the select step after submit.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function submitAndShowSelect(array &$form, FormStateInterface $form_state): AjaxResponse {
    return $this->submitStep($form, $form_state, 'select');
  }

  /**
   * Submit and show the review step after submit.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function submitAndShowReview(array &$form, FormStateInterface $form_state): AjaxResponse {
    return $this->submitStep($form, $form_state, 'review');
  }

  /**
   * Submit and show the embed step after submit.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function submitAndShowEmbed(array $form, FormStateInterface $form_state): AjaxResponse {
    return $this->submitStep($form, $form_state, 'embed');
  }

  /**
   * Form submission handler for the embed step.
   *
   * On success this will submit the command to save the embedded entity to the
   * WYSIWYG element, and then close the modal dialog. On form errors, this will
   * rebuild the form and display the error messages.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function submitEmbedStep(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    // Submit configuration form the selected Entity Embed Display plugin.
    $entity_element = $form_state->getValue('attributes');
    $entity = $this->entityTypeManager->getStorage('media')
      ->loadByProperties(['uuid' => $entity_element['data-entity-uuid']]);
    $entity = current($entity);

    $values = $form_state->getValues();
    // Display errors in form, if any.
    if ($form_state->hasAnyErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#media-embed-dialog-form', $form));

      return $response;
    }

    $values['attributes'] = $this->prepareAttributes($values['attributes'], $entity);

    $response->addCommand(new EditorDialogSave($values));
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

  /**
   * Registers JS callbacks.
   *
   * Callbacks are responsible for getting entities from entity browser and
   * updating form values accordingly.
   *
   * @param \Drupal\entity_browser\Events\RegisterJSCallbacks $event
   *   The entity browser veent.
   */
  public function registerJsCallback(RegisterJSCallbacks $event): void {
    if ($event->getBrowserID() == $this->entityBrowser->id()) {
      $event->registerCallback('Drupal.mediaEmbedDialog.selectionCompleted');
    }
  }

  /**
   * Load the current entity browser and its settings from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function loadEntityBrowser(FormStateInterface $form_state): void {
    $this->entityBrowser = NULL;
    $this->entityBrowserSettings = [];

    /** @var \Drupal\embed\EmbedButtonInterface $embed_button */
    $embed_button = $form_state->get('embed_button');

    if ($embed_button && $entity_browser_id = $embed_button->getTypePlugin()->getConfigurationValue('entity_browser')) {
      $this->entityBrowser = $this->entityTypeManager->getStorage('entity_browser')->load($entity_browser_id);
      $this->entityBrowserSettings = $embed_button->getTypePlugin()->getConfigurationValue('entity_browser_settings');
    }
  }

  /**
   * Prepares the attributes for the oEmbed tag.
   *
   * @param array $attributes
   *   The attributes.
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return array
   *   The attributes.
   */
  protected function prepareAttributes(array $attributes, MediaInterface $media): array {
    // Filter out empty attributes.
    $attributes = array_filter($attributes, function ($value) {
      return (bool) mb_strlen((string) $value);
    });

    // Unset the button.
    if (isset($attributes['data-embed-button'])) {
      unset($attributes['data-embed-button']);
    }

    $uuid = $attributes['data-entity-uuid'];
    unset($attributes['data-entity-uuid']);
    $query = [];
    if (isset($attributes['data-entity-view-mode'])) {
      $query['view_mode'] = $attributes['data-entity-view-mode'];
      unset($attributes['data-entity-view-mode']);
    }
    $resource_url = Url::fromUri('https://data.ec.europa.eu/ewp/media/' . $uuid, ['query' => $query]);
    $attributes['data-oembed'] = Url::fromUri('https://oembed.ec.europa.eu', ['query' => ['url' => $resource_url->toString()]])->toString();
    $attributes['data-resource-url'] = $resource_url->setOption('query', [])->toString();
    $attributes['data-resource-label'] = $media->label();

    return $attributes;
  }

  /**
   * Returns the form element required to select a media view mode.
   *
   * @param array $entity_element
   *   The entity element values from the form.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Th media entity.
   *
   * @return array
   *   The form element.
   */
  protected function getMediaViewModeFormElement(array $entity_element, EntityInterface $entity): array {
    // @todo add element alignment and caption if the relevant filters are
    // enabled. See EntityEmbedDialog for example.
    // Allow to specify a view mode if the media type has more than 1.
    $display_options = $this->getMediaViewModeOptions($entity);

    // If no view mode was found for this media type,
    // prompt the user to enable one.
    if (empty($display_options)) {
      $form_element['data-entity-view-mode-warning'] = [
        '#type' => 'inline_template',
        '#template' => '<div>{{ warning }}</div>',
        '#context' => [
          'warning' => $this->t('There is no embeddable view mode for this media type.'),
        ],
      ];
      if ($this->moduleHandler->moduleExists('field_ui')) {
        $media_type_storage = $this->entityTypeManager->getStorage('media_type');
        $media_type = $media_type_storage->load($entity->bundle());
        $form_element['data-entity-view-mode-link'] = [
          '#type' => 'link',
          '#title' => $this->t('Manage @media view modes', ['@media' => $media_type->label()]),
          '#url' => Url::fromRoute('entity.entity_view_display.' . $entity->getEntityTypeId() . '.default', [
            'media_type' => $entity->bundle(),
          ]),
          '#attributes' => ['target' => '_blank'],
        ];;
      }

      return $form_element;
    }

    // If there is only one display, use it and don't ask for input.
    if (count($display_options) === 1) {
      reset($display_options);

      return [
        'data-entity-view-mode' => [
          '#type' => 'value',
          '#value' => key($display_options),
        ],
      ];
    }

    // If there is more than one display, add a select field.
    return [
      'data-entity-view-mode' => [
        '#type' => 'select',
        '#title' => $this->t('Display as'),
        '#options' => $display_options,
        '#default_value' => $entity_element['data-entity-view-mode'],
        '#required' => TRUE,
      ],
    ];
  }

  /**
   * Gets the options to be used for the View mode selector.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return array
   *   The options.
   */
  protected function getMediaViewModeOptions(MediaInterface $media): array {
    $bundle = $media->bundle();
    $view_display_storage = $this->entityTypeManager->getStorage('entity_view_display');
    $displays = $view_display_storage->getQuery()
      ->condition('targetEntityType', 'media')
      ->condition('bundle', $bundle)
      ->condition('status', TRUE)
      ->execute();

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface[] $displays */
    $displays = $view_display_storage->loadMultiple($displays);
    $view_mode_storage = $this->entityTypeManager->getStorage('entity_view_mode');
    $options = [];
    foreach ($displays as $display) {
      if ($display->getThirdPartySetting('oe_media_embed', 'embeddable')) {
        $options[$display->getMode()] = $display->getMode() === 'default' ? $this->t('Default') : $view_mode_storage->load('media.' . $display->getMode())->label();
      }
    }

    return $options;
  }

}
