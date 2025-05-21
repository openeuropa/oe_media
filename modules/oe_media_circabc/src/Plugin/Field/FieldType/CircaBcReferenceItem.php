<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Url;

/**
 * Defines the 'oe_media_circabc_circabc_reference' field type.
 */
#[FieldType(
  id: "oe_media_circabc_circabc_reference",
  label: new TranslatableMarkup("CircaBC Reference"),
  category: "general",
  default_widget: "oe_media_circabc_default_widget",
  default_formatter: "oe_media_circabc_default",
)]
class CircaBcReferenceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $uuid = $this->get('uuid')->getValue();
    return $uuid === NULL || $uuid === '';
  }

  /**
   * Return the URL to the actual remote file.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  public function getFileUrl(): Url {
    return Url::fromUri(Settings::get('circabc')['url'] . '/d/d/workspace/SpacesStore/' . $this->get('uuid')->getValue() . '/download');
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['uuid'] = DataDefinition::create('string')
      ->setLabel(t('UUID'))
      ->setRequired(TRUE);

    $properties['filename'] = DataDefinition::create('string')
      ->setLabel(t('Name'));

    $properties['mime'] = DataDefinition::create('string')
      ->setLabel(t('Mime'));

    $properties['size'] = DataDefinition::create('integer')
      ->setLabel(t('Size'))
      ->setSetting('size', 'big')
      ->setSetting('unsigned', TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = [
      'uuid' => [
        'type' => 'varchar',
        'not null' => FALSE,
        'description' => 'UUID.',
        'length' => 128,
      ],
      'filename' => [
        'type' => 'varchar',
        'not null' => FALSE,
        'description' => 'Name.',
        'length' => 255,
      ],
      'mime' => [
        'type' => 'varchar',
        'not null' => FALSE,
        'description' => 'Mime.',
        'length' => 255,
      ],
      'size' => [
        'type' => 'int',
        'description' => 'Size.',
        'length' => 255,
        'unsigned' => TRUE,
        'size' => 'big',
      ],
    ];

    $schema = [
      'columns' => $columns,
    ];

    return $schema;
  }

}
