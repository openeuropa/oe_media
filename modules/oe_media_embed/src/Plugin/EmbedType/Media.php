<?php

declare(strict_types = 1);

namespace Drupal\oe_media_embed\Plugin\EmbedType;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\embed\EmbedType\EmbedTypeBase;
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultIconUrl() {
    return file_create_url(drupal_get_path('module', 'oe_media_embed') . '/js/plugins/embed_media/embed.png');
  }

}
