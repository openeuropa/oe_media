<?php

declare(strict_types = 1);

namespace Drupal\oe_media_iframe\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\oe_media_iframe\Plugin\media\Source\Iframe;

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
class MediaIframeFormatter extends FormatterBase {

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

    if ($media_type && $media_type->getSource() instanceof Iframe) {
      return TRUE;
    }

    return parent::isApplicable($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $item->value,
        '#allowed_tags' => ['iframe'],
      ];
    }

    return $elements;
  }

}
