<?php

declare(strict_types = 1);

namespace Drupal\oe_media_embed\Plugin\EmbedType;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\embed\EmbedType\EmbedTypeBase;
use Drupal\entity_browser\EntityBrowserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Embeds Media entities in a Drupal-agnostic way.
 *
 * @EmbedType(
 *   id = "embed_media",
 *   label = @Translation("Media"),
 * )
 */
class Media extends EmbedTypeBase implements ContainerFactoryPluginInterface {

  use PluginDependencyTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'media_types' => [
        'av_portal_photo' => 'av_portal_photo',
        'av_portal_video' => 'av_portal_video',
        'document' => 'document',
        'image' => 'image',
        'remote_video' => 'remote_video',
      ],
      'entity_browser' => '',
      'entity_browser_settings' => [
        'display_review' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $media_types = $this->entityTypeManager->getStorage('media_type')->loadMultiple();
    $options = [];
    foreach ($media_types as $media_type) {
      $options[$media_type->id()] = $media_type->label();
    }

    $form['media_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Media type'),
      '#options' => $options,
      '#default_value' => $this->getConfigurationValue('media_types'),
      '#required' => TRUE,
    ];

    $form['entity_browser'] = [
      '#type' => 'value',
      '#value' => '',
    ];

    /** @var \Drupal\entity_browser\EntityBrowserInterface[] $browsers */
    if ($this->entityTypeManager->hasDefinition('entity_browser') && ($browsers = $this->entityTypeManager->getStorage('entity_browser')->loadMultiple())) {
      // Filter out unsupported displays & return array of ids and labels.
      $browsers = array_map(
        function ($item) {
          /** @var \Drupal\entity_browser\EntityBrowserInterface $item */
          return $item->label();
        },
        // Filter out both modal and standalone forms as they don't work.
        array_filter($browsers, function (EntityBrowserInterface $browser) {
          return !in_array($browser->getDisplay()->getPluginId(), ['modal', 'standalone'], TRUE);
        })
      );
      $options = ['_none' => $this->t('None (autocomplete)')] + $browsers;
      $form['entity_browser'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity browser'),
        '#description' => $this->t('Entity browser to be used to select entities to be embedded. Only compatible browsers will be available to be chosen.'),
        '#options' => $options,
        '#default_value' => $this->getConfigurationValue('entity_browser'),
      ];
      $form['entity_browser_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Entity browser settings'),
        '#open' => TRUE,
        '#states' => [
          'invisible' => [
            ':input[name="type_settings[entity_browser]"]' => ['value' => '_none'],
          ],
        ],
      ];
      $form['entity_browser_settings']['display_review'] = [
        '#type' => 'checkbox',
        '#title' => 'Display the entity after selection',
        '#default_value' => $this->getConfigurationValue('entity_browser_settings')['display_review'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $entity_browser = $form_state->getValue('entity_browser') == '_none' ? '' : $form_state->getValue('entity_browser');
    $form_state->setValue('entity_browser', $entity_browser);
    // @todo is this needed to enforce configuration correctness?
    $form_state->setValue('entity_browser_settings', $form_state->getValue('entity_browser_settings'));

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->addDependencies(parent::calculateDependencies());

    $entity_browser = $this->getConfigurationValue('entity_browser');
    if ($entity_browser && $this->entityTypeManager->hasDefinition('entity_browser')) {
      $browser = $this->entityTypeManager->getStorage('entity_browser')->load($entity_browser);
      if ($browser) {
        $this->addDependency($browser->getConfigDependencyKey(), $browser->getConfigDependencyName());
      }
    }

    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultIconUrl() {
    return file_create_url(drupal_get_path('module', 'oe_media_embed') . '/js/plugins/embed_media/embed.png');
  }

}
