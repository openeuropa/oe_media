<?php

declare(strict_types=1);

namespace Drupal\oe_media_iframe\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter that removes any HTML except the first <iframe> tag.
 *
 * @Filter(
 *   id = "filter_iframe_tag",
 *   title = @Translation("Single iframe tag"),
 *   description = @Translation("Filters out all the HTML apart from the first <code>iframe</code> tag."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR,
 * )
 */
class FilterIframeTag extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    // Do a quick string check to see if a possible iframe tag is present.
    // This does not account for tags wrapped in HTML comments, but speeds up
    // the processor.
    if (stristr($text, '<iframe') === FALSE) {
      $result->setProcessedText('');
      return $result;
    }

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $iframes = $xpath->query('//iframe[1]');
    if ($iframes->count() === 0) {
      $result->setProcessedText('');
      return $result;
    }

    $iframe = $iframes->item(0);
    // Remove all the iframe content that is not simple text. The iframe
    // contents are displayed in user agents where iframes are not supported
    // or disabled.
    // We cannot remove the elements directly in the first loop, as any changes
    // to \DOMNodeList will break the loop. Collect instead the nodes to remove
    // and loop later on them.
    // @see https://www.php.net/manual/en/class.domnodelist.php
    $to_remove = [];
    foreach ($iframe->childNodes as $node) {
      if ($node->nodeType !== XML_TEXT_NODE) {
        $to_remove[] = $node;
      }
    }
    foreach ($to_remove as $node) {
      $iframe->removeChild($node);
    }

    // Since the text can come with multiple iframes and other HTML entities
    // which we don't want to include in the processed markup, we take only the
    // modified iframe and import it in a new HTML document.
    $new_dom = Html::load($dom->saveHTML($iframe));

    $result->setProcessedText(Html::serialize($new_dom));

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Only one <code>iframe</code> tag allowed. All other content will be stripped.');
  }

}
