<?php

declare(strict_types=1);

namespace Drupal\oe_media_webtools\Plugin\Field\FieldWidget;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_browser\EntityBrowserFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'OP Publication List ID' field widget.
 *
 * @FieldWidget(
 *   id = "oe_media_op_publication_lists_id",
 *   label = @Translation("OP Publication List ID"),
 *   field_types = {
 *     "json"
 *   }
 * )
 */
class OpPublicationListIdWidget extends StringTextfieldWidget implements ContainerFactoryPluginInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a OpPublicationListIdWidget object.
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
   * @param \Drupal\Core\Language\LanguageManagerInterface|null $language_manager
   *   The language manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ?LanguageManagerInterface $language_manager = NULL) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->languageManager = $language_manager ?? \Drupal::service('language_manager');
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
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // If we already have a json string, we get the widget id to set it as
    // default value.
    if ($items[$delta]->getString()) {
      $values = Json::decode($items[$delta]->getString());
      if ($values) {
        $element['value']['#default_value'] = $values['widgetId'];
      }
    }

    $element['value']['#title'] = $this->t('Webtools OP Publication list ID');
    $element['value']['#type'] = 'number';
    $element['value']['#min'] = 0;
    $element['value']['#description'] = $this->t('Enter the widget id of the snippet generated on the <a href=":op_widget_url" target="_blank">OP Website</a>.', [':op_widget_url' => 'https://op.europa.eu/en/my-widgets']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    if (!$form_state->getFormObject() instanceof EntityBrowserFormInterface) {
      if (!$form_state->getFormObject()->getEntity()->isDefaultTranslation()) {
        $lang = $form_state->getFormObject()->getEntity()->getUntranslated()->language()->getId();
      }
      elseif ($form_state->hasValue('langcode') && isset($form_state->getValue('langcode')[0]['value'])) {
        $language_code = $form_state->getValue('langcode')[0]['value'];
        if ($language_code !== 'und' && $language_code !== 'zxx' && $this->languageManager->getLanguage($language_code)) {
          $lang = $language_code;
        }
      }
    }
    $values = parent::massageFormValues($values, $form, $form_state);
    foreach ($values as $delta => &$item_values) {
      $id = $item_values['value'];
      $item_values['value'] = Json::encode([
        'utility' => 'opwidget',
        'widgetId' => $id,
        'lang' => $lang ?? 'auto',
      ]);
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if (!parent::isApplicable($field_definition) ||
      $field_definition->getTargetEntityTypeId() !== 'media' ||
      $field_definition->getTargetBundle() !== 'webtools_op_publication_list'
    ) {
      return FALSE;
    }
    return TRUE;
  }

}
