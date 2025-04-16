<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

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
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['uuid'] = $element + [
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->uuid ?? NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#attributes' => ['class' => ['js-text-full', 'text-full']],
    ];
    return $element;
  }

}
