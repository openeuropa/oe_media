<?php

declare(strict_types = 1);

namespace Drupal\oe_media_webtools\Plugin\Field\FieldWidget;

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

    // If we already have a json string, we get the id to set it as default
    // value.
    $id = '';
    if ($items[$delta]->getValue()) {
      if (str_contains($items[$delta]->getValue()['value'], '{ "service": "opwidget", "widgetId": "')) {
        $id = str_replace('{ "service": "opwidget", "widgetId": "', '', $items[$delta]->getValue()['value']);
        $id = str_replace('" }', '', $id);
      }
    }

    $element['value']['#type'] = 'textfield';
    $element['value']['#description'] = $this->t('Enter the widget id of the snippet generated in <a href="https://op.europa.eu/en/my-widgets" target="_blank">OP Website</a>.');
    $element['value']['#default_value'] = $id;
    $element['value']['#element_validate'] = [
      [get_called_class(), 'validateInteger'],
    ];

    return $element;
  }

  /**
   * Validate callback to ensure that the input is a numeric value.
   *
   * @param array $element
   *   The element array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateInteger(array $element, FormStateInterface $form_state) {
    if (!is_numeric($element['#value']) ||
      strpos($element['#value'], ',') ||
      strpos($element['#value'], '.')) {
      $form_state->setError($element, t('The @title allows only integer values.', ['@title' => $element['#title']]));
    }
    if ($element['#value'] < 0) {
      $form_state->setError($element, t('The @title allows only positive values.', ['@title' => $element['#title']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    foreach ($values as $delta => &$item_values) {
      $id = $item_values['value'];
      $item_values['value'] = '{ "service": "opwidget", "widgetId": "' . $id . '" }';
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_bundle = $field_definition->getTargetBundle();

    if (!parent::isApplicable($field_definition) ||
      $field_definition->getTargetEntityTypeId() !== 'media' ||
      $target_bundle !== 'webtools_op_publication_list') {
      return FALSE;
    }
    return TRUE;
  }

}
