<?php

declare(strict_types = 1);

namespace Drupal\oe_media_webtools\Plugin\Field\FieldWidget;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;

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
class OpPublicationListIdWidget extends StringTextfieldWidget {

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

    $element['value']['#type'] = 'number';
    $element['value']['#min'] = 0;
    $element['value']['#description'] = $this->t('Enter the widget id of the snippet generated on the <a href="https://op.europa.eu/en/my-widgets" target="_blank">OP Website</a>.');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    foreach ($values as $delta => &$item_values) {
      $id = $item_values['value'];
      $item_values['value'] = Json::encode([
        'service' => 'opwidget',
        'widgetId' => $id,
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
