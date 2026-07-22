<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\oe_media_circabc\CircaBc\CircaBcClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'oe_media_circabc_default_widget' widget.
 */
#[FieldWidget(
  id: 'oe_media_circabc_default_widget',
  label: new TranslatableMarkup('CircaBC Default Reference'),
  field_types: ['oe_media_circabc_circabc_reference'],
)]
class CircaBcReferenceWidget extends StringTextfieldWidget {

  /**
   * Constructs a CircaBcReferenceWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\oe_media_circabc\CircaBc\CircaBcClientInterface $circaBcClient
   *   The CircaBC client.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, protected CircaBcClientInterface $circaBcClient) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
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
      $container->get('oe_media_circabc.client'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $uuid = $items[$delta]->uuid ?? NULL;

    $element['uuid'] = $element + [
      '#type' => 'textfield',
      '#default_value' => $uuid,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#attributes' => ['class' => ['js-text-full', 'text-full']],
    ];

    /** @var \Drupal\oe_media_circabc\Plugin\Field\FieldType\CircaBcReferenceItem $item */
    if (!empty($uuid)) {
      $interest_group = $this->circaBcClient->getDocumentInterestGroup($uuid);
      // Group 0 will also work as a backup, with some limitations.
      $group_uuid = $interest_group['id'] ?? '0';
      $link = Settings::get('circabc')['url'] . "/ui/group/$group_uuid/library/$uuid/details";
      $element['uuid']['#description'] = $this->t('Full circaBC url: @link', ['@link' => Link::fromTextAndUrl($link, Url::fromUri($link))->toString()]);
    }

    return $element;
  }

}
