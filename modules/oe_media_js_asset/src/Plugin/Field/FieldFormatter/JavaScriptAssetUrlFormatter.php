<?php

declare(strict_types = 1);

namespace Drupal\oe_media_js_asset\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\oe_media_js_asset\Plugin\media\Source\JavaScriptAsset;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation for the "oe_media_js_asset_url" formatter plugin.
 *
 * @FieldFormatter(
 *   id = "oe_media_js_asset_url",
 *   label = @Translation("JavaScript asset URL"),
 *   description = @Translation("Renders the JavaScript asset URL for media entities."),
 *   field_types = {
 *     "oe_media_js_asset_url"
 *   }
 * )
 */
class JavaScriptAssetUrlFormatter extends FormatterBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a JavaScriptAssetUrlFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->configFactory = $config_factory;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getTargetEntityTypeId() !== 'media') {
      return FALSE;
    }

    $bundle_id = $field_definition->getTargetBundle();
    if ($bundle_id === NULL) {
      return FALSE;
    }

    /** @var \Drupal\media\MediaTypeInterface $media_type */
    $media_type = \Drupal::entityTypeManager()->getStorage('media_type')->load($bundle_id);

    return $media_type && $media_type->getSource() instanceof JavaScriptAsset;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $config = $this->configFactory->get('oe_media_js_asset.settings');
    $environments = $config->get('environments');

    foreach ($items as $delta => $item) {
      if (!empty($environments[$item->environment]['url'])) {
        $elements[$delta] = [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#value' => '',
          '#attributes' => [
            'src' => $environments[$item->environment]['url'] . $item->path,
          ],
        ];
      }
    }

    return $elements;
  }

}
