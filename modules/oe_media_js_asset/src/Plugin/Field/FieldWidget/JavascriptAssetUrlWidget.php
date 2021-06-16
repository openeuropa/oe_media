<?php

declare(strict_types = 1);

namespace Drupal\oe_media_js_asset\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'javascript_asset_url' widget.
 *
 * @FieldWidget(
 *   id = "javascript_asset_url",
 *   label = @Translation("Javascript asset URL"),
 *   field_types = {
 *     "javascript_asset_url"
 *   }
 * )
 */
class JavascriptAssetUrlWidget extends WidgetBase {

  /**
   * Form element validation handler for the 'relative_path' element.
   */
  public static function validateRelativePathElement($element, FormStateInterface $form_state, $form): void {
    if (substr($element['#value'], 0, 1) !== '/') {
      $form_state->setError($element, t('Manually entered paths should start with: /'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];

    $element['javascript_asset_url'] = [
      '#type' => 'details',
      '#title' => $this->t('Javascript asset URL'),
      '#open' => TRUE,
    ] + $element;

    $element['javascript_asset_url']['environment'] = [
      '#type' => 'select',
      '#title' => $this->t('Environment'),
      '#default_value' => !$item->isEmpty() ? $item->environment : '',
      '#maxlength' => 255,
      '#options' => $this->getEnvironmentOptions(),
      '#required' => FALSE,
    ];

    $element['javascript_asset_url']['relative_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Javascript relative path'),
      '#element_validate' => [[get_called_class(), 'validateRelativePathElement']],
      '#maxlength' => 1024,
      '#default_value' => !$item->isEmpty() ? $item->relative_path : '',
      '#required' => FALSE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      $asset_url = $item['javascript_asset_url'];
      $item = [
        'environment' => $asset_url['environment'],
        'relative_path' => $asset_url['relative_path'],
      ];
    }

    return $values;
  }

  /**
   * Get the allowed environment field options from env settings.
   *
   * @return array
   *   The allowed values for the environment field.
   */
  protected function getEnvironmentOptions(): array {
    $options = [];

    $acceptance = getenv('JS_ASSET_ACC_URL');
    if (!empty($acceptance)) {
      $options[$acceptance] = $this->t('Acceptance');
    }

    $production = getenv('JS_ASSET_PROD_URL');
    if (!empty($production)) {
      $options[$production] = $this->t('Production');
    }

    return $options;
  }

}
