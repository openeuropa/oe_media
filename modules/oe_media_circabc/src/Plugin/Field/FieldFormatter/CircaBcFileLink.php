<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'oe_media_circabc_default' formatter.
 */
#[FieldFormatter(
  id: 'oe_media_circabc_default',
  label: new TranslatableMarkup('CircaBC Default'),
  field_types: ['oe_media_circabc_circabc_reference'],
)]
class CircaBcFileLink extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $new_element = [
        '#theme' => 'file_link_formatter',
        '#link' => [
          '#type' => 'link',
          '#title' => $item->filename,
          '#url' => $item->getFileUrl(),
        ],
        '#size' => ByteSizeMarkup::create((int) $item->size, $langcode),
        '#format' => $item->mime,
      ];

      $element[$delta] = $new_element;
    }
    return $element;
  }

}
