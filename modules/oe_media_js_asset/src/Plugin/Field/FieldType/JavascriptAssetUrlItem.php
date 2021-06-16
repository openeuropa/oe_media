<?php

declare(strict_types = 1);

namespace Drupal\oe_media_js_asset\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'javascript_asset_url' field type.
 *
 * @FieldType(
 *   id = "javascript_asset_url",
 *   label = @Translation("Javascript asset url"),
 *   module = "oe_media_js_asset",
 *   description = @Translation("Stores the javascript asset media url."),
 *   default_widget = "javascript_asset_url"
 * )
 */
class JavascriptAssetUrlItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'environment' => [
          'description' => 'Environment the javascript asset is fetched from.',
          'type' => 'varchar',
          'length' => 255,
        ],
        'relative_path' => [
          'description' => 'The relative path of the javascript asset.',
          'type' => 'varchar',
          'length' => 1024,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $env = $this->get('environment')->getValue();
    $path = $this->get('relative_path')->getValue();

    return $env === NULL || $env === '' || $path === NULL || $path === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['environment'] = DataDefinition::create('string')
      ->setLabel(t('Environment'));
    $properties['relative_path'] = DataDefinition::create('string')
      ->setLabel(t('Javascript relative path'));

    return $properties;
  }

}
