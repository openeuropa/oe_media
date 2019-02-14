<?php

declare(strict_types = 1);

namespace Drupal\oe_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\UrlResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Entity browser widget linking to the AV Portal service for uploading videos.
 *
 * @EntityBrowserWidget(
 *   id = "oe_media_reference_by_url",
 *   label = @Translation("OEMedia Reference by url"),
 *   description = @Translation("Reference a media widget by url"),
 *   auto_select = FALSE
 * )
 */
class OEMediaReferenceByUrl extends WidgetBase {

  /**
   * Constructs widget plugin.
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
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   *   The OEmbed url resolver.
   *
   * @internal param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository The entity display repository.*   The entity display repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, UrlResolverInterface $url_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->urlResolver = $url_resolver;
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
      $container->get('entity_type.bundle.info'),
      $container->get('media.oembed.url_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    if (empty($this->configuration['bundle'])) {
      return ['#markup' => $this->t('The bundle setting is not configured correctly.')];
    }

    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    $form['entity_browser_reference_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Resource URL'),
      /* @TODO: Accepted resources */
      '#description' => $this->t('Accepted resources are ...'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * We need to create the entities if they don't exist already
   * exist.
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {

    // Get the sources from the allowed bundles.
    $allowed_bundles = $this->configuration['bundle'];
    $sources = [];

    foreach ($allowed_bundles as $allowed_bundle) {
      $bundle_info = $this->entityTypeManager->getStorage('media_type')
        ->load($allowed_bundle);

      $source = $bundle_info->getSource();
      $config = $source->getConfiguration();

      $sources[$source->getPluginId()][] = [
        'bundle' => $allowed_bundle,
        'source_field' => $config['source_field'],
        'definition' => $source,
      ];
    }

    $url = $form_state->getUserInput()['entity_browser_reference_url'];
    if (empty($url)) {
      return [];
    }

    // Detect which bundle should be created.
    // For OEmbed resources.
    if (in_array('oembed:video', array_keys($sources))) {
      $bundle_info = $this->getOembedBundleInfo($url, $sources);
    }

    // For AVPortal resources.
    if (empty($bundle) && (in_array('media_av_portal_video', array_keys($sources)) || in_array('media_av_portal_video', array_keys($sources)))) {
      $bundle_info = $this->getAvPortalBundle($url, $sources);
    }

    // $url is not mappeable to any bundle.
    if (empty($bundle_info)) {
      $form_state->setError($form['widget']['entity_browser_reference_url'], $this->t('It was not possible to find any media bundle that accepts that pattern.'));
      return [];
    }

    // Try to load the entity.
    $entities = $this->entityTypeManager->getStorage('media')
      ->loadByProperties([$bundle_info['source_field'] => $url]);

    // Create if it does not exist().
    if (empty($entities)) {
      $entity = $this->entityTypeManager->getStorage('media')->create([
        'bundle' => $bundle_info['bundle'],
        $bundle_info['source_field'] => $url,
      ]);
      $entity->save();
      $entities = [$entity];
    }

    return $entities;

  }

  /**
   * Finds the bundle and field that accept that oembed resource url.
   *
   * @param string $url
   *   The url.
   * @param array $plugins
   *   The associated plugins.
   *
   * @return array|mixed|null
   *   The associated bundle, source and source field if valid.
   */
  protected function getOembedBundleInfo(string $url, array $plugins): ?array {
    try {

      foreach ($plugins as $pluginId => $sources) {
        // Ensure that the URL matches a provider.
        $provider = $this->urlResolver->getProviderByUrl($url);

        // If it does, no exception thrown,  bundle is the first one
        // implementing the media source.
        foreach ($sources as $source) {
          if (!empty($source['definition']->getConfiguration()['providers']) && in_array($provider->getName(), array_values($source['definition']->getConfiguration()['providers']))) {
            return [
              'bundle' => $source['bundle'],
              'source_field' => $source['source_field'],
            ];
          }
        }
      }

    }
    catch (ResourceException $e) {

    }

    return NULL;
  }

  /**
   * Finds the bundle and field that accept that AvPortal resource url.
   *
   * @param string $url
   *   The url.
   * @param array $plugins
   *   The associated plugins.
   *
   * @return array|mixed|null
   *   The associated bundle, source and source field if valid.
   */
  protected function getAvPortalBundleInfo(string $url, array $plugins) : ?array {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'bundle' => NULL,
      'submit_text' => $this->t('Save entity'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $entities = $this->prepareEntities($form, $form_state);
    $this->selectEntities($entities, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $definitions = $this->entityTypeBundleInfo->getBundleInfo('media');
    $bundles = array_map(function ($item) {
      return $item['label'];
    }, $definitions);

    $form['bundle'] = [
      '#type' => 'container',
      'select' => [
        '#type' => 'select',
        '#title' => $this->t('Bundles'),
        '#options' => $bundles,
        '#multiple' => TRUE,
        '#default_value' => $this->configuration['bundle'] ?? '',
      ],
      '#attributes' => ['id' => 'bundle-wrapper-' . $this->uuid()],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['bundle'] = $this->configuration['bundle']['select'];
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    return AccessResult::allowed();
  }

}
