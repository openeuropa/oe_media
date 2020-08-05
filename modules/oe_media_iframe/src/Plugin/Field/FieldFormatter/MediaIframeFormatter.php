<?php

declare(strict_types = 1);

namespace Drupal\oe_media_iframe\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\oe_media_iframe\Plugin\media\Source\Iframe;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation for the "oe_media_iframe" formatter plugin.
 *
 * @FieldFormatter(
 *   id = "oe_media_iframe",
 *   label = @Translation("Media iframe"),
 *   description = @Translation("Renders the iframe for media entities with iframe sources."),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class MediaIframeFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a MediaIframeFormatter instance.
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
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

    return $media_type && $media_type->getSource() instanceof Iframe;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $media_type = $this->entityTypeManager->getStorage('media_type')->load($this->fieldDefinition->getTargetBundle());
    // Add cacheable dependency from the media type.
    $cacheable_metadata = CacheableMetadata::createFromRenderArray($elements)
      ->addCacheableDependency($media_type);

    $text_format = $media_type->getSource()->getConfiguration()['text_format'] ?? NULL;

    foreach ($items as $delta => $item) {
      if ($text_format) {
        $elements[$delta] = [
          '#type' => 'processed_text',
          '#text' => $item->value,
          '#format' => $text_format,
        ];
        // Add cacheable dependency from the used filter format.
        $cacheable_metadata->addCacheableDependency($this->entityTypeManager->getStorage('filter_format')->load($text_format));
      }
      else {
        // Fallback in case we don't have the selected text format.
        $elements[$delta] = [
          '#markup' => $item->value,
          '#allowed_tags' => ['iframe'],
        ];
      }
    }

    $cacheable_metadata->applyTo($elements);

    return $elements;
  }

}
