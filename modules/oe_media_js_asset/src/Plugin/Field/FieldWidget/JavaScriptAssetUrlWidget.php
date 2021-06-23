<?php

declare(strict_types = 1);

namespace Drupal\oe_media_js_asset\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'oe_media_js_asset_url' widget.
 *
 * @FieldWidget(
 *   id = "oe_media_js_asset_url",
 *   label = @Translation("JavaScript asset URL"),
 *   field_types = {
 *     "oe_media_js_asset_url"
 *   }
 * )
 */
class JavaScriptAssetUrlWidget extends WidgetBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigFactory $configFactory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];

    $element['asset_url'] = [
      '#type' => 'details',
      '#title' => $this->t('JavaScript asset URL'),
      '#open' => TRUE,
    ] + $element;

    $element['asset_url']['environment'] = [
      '#type' => 'select',
      '#title' => $this->t('Environment'),
      '#default_value' => !$item->isEmpty() ? $item->environment : '',
      '#options' => $this->getEnvironmentOptions(),
    ];

    $element['asset_url']['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#description' => $this->t('A relative path to the JS asset. It should always start with a "/" character.'),
      '#element_validate' => [[get_called_class(), 'validatePathElement']],
      '#maxlength' => 2048,
      '#default_value' => !$item->isEmpty() ? $item->path : '',
    ];

    return $element;
  }

  /**
   * Form element validation handler for the 'path' element.
   */
  public static function validatePathElement($element, FormStateInterface $form_state, $form): void {
    if (!empty($element['#value']) && substr($element['#value'], 0, 1) !== '/' && !UrlHelper::isValid($element['#value'], TRUE)) {
      $form_state->setError($element, t('Path should start with: /'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      $asset_url = $item['asset_url'];
      $item = [
        'environment' => $asset_url['environment'],
        'path' => $asset_url['path'],
      ];
    }

    return $values;
  }

  /**
   * Get the available environment options from config settings.
   *
   * @return array
   *   The allowed values for the environment field.
   */
  protected function getEnvironmentOptions(): array {
    $config = $this->configFactory->get('oe_media_js_asset.settings');
    $options = [];
    foreach ($config->get('environments') as $name => $values) {
      $options[$name] = $values['label'];
    }

    return $options;
  }

}
