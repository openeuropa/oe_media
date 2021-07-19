<?php

declare(strict_types = 1);

namespace Drupal\oe_media_js_asset\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'oe_media_js_asset_url' field type.
 *
 * @FieldType(
 *   id = "oe_media_js_asset_url",
 *   label = @Translation("JavaScript asset URL"),
 *   module = "oe_media_js_asset",
 *   description = @Translation("Stores the JS asset media URL."),
 *   default_widget = "oe_media_js_asset_url"
 * )
 */
class JavaScriptAssetUrlItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'environment' => [
          'description' => 'Environment the JavaScript asset is fetched from.',
          'type' => 'varchar',
          'length' => 255,
        ],
        'path' => [
          'description' => 'The relative path of the JavaScript asset.',
          'type' => 'varchar',
          'length' => 2048,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $env = $this->get('environment')->getValue();
    $path = $this->get('path')->getValue();

    return $env === NULL || $env === '' || $path === NULL || $path === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['environment'] = DataDefinition::create('string')
      ->setLabel(t('Environment'));
    $properties['path'] = DataDefinition::create('string')
      ->setLabel(t('JavaScript relative path'));

    return $properties;
  }

}
