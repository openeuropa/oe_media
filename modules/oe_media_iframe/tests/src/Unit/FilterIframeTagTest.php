<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media_iframe\Unit;

use Drupal\oe_media_iframe\Plugin\Filter\FilterIframeTag;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the filter_iframe_tag filter plugin.
 *
 * @group oe_media_iframe
 * @coversDefaultClass \Drupal\oe_media_iframe\Plugin\Filter\FilterIframeTag
 */
class FilterIframeTagTest extends UnitTestCase {

  /**
   * Tests the filter process function.
   *
   * @param string $html
   *   The html to filter.
   * @param string $expected
   *   The expected html.
   *
   * @dataProvider processDataProvider
   * @covers ::process
   */
  public function testProcess(string $html, string $expected): void {
    $filter = new FilterIframeTag([], 'filter_iframe_tag', ['provider' => 'test']);
    $filter->setStringTranslation($this->getStringTranslationStub());

    $processed_text = $filter->process($html, NULL)->getProcessedText();
    $this->assertSame($expected, $processed_text);
  }

  /**
   * Data provider for testProcess().
   *
   * @return array
   *   The test data.
   */
  public function processDataProvider(): array {
    return [
      'single iframe' => [
        '<iframe src="http://example.com" width="800" height="600" allowFullScreen="true"></iframe>',
        '<iframe src="http://example.com" width="800" height="600" allowfullscreen="true"></iframe>',
      ],
      'multiple iframes' => [
        '<iframe src="http://example.com/first"></iframe><iframe src="http://example.com/second"></iframe>',
        '<iframe src="http://example.com/first"></iframe>',
      ],
      'HTML comment' => [
        '<!--<iframe src="http://example.com/commented"></iframe>--><iframe src="http://example.com"></iframe><!-- More comments -->',
        '<iframe src="http://example.com"></iframe>',
      ],
      'nested tags' => [
        '<iframe src="http://example.com"><a href="http://dangerous-domain.example">Click here!</a>Please enable iframes in your browser.</iframe>',
        '<iframe src="http://example.com">&lt;a href="http://dangerous-domain.example"&gt;Click here!&lt;/a&gt;Please enable iframes in your browser.</iframe>',
      ],
      'nested iframes' => [
        '<iframe src="http://example.com/first"><iframe src="http://example.com/second">Inner content.</iframe>Useful content.</iframe>',
        '<iframe src="http://example.com/first">&lt;iframe src="http://example.com/second"&gt;Inner content.</iframe>',
      ],
      'multiple text content' => [
        '<iframe src="http://example.com">First node <strong>remove</strong> second node <em>remove</em>.</iframe>',
        '<iframe src="http://example.com">First node &lt;strong&gt;remove&lt;/strong&gt; second node &lt;em&gt;remove&lt;/em&gt;.</iframe>',
      ],
      'extra HTML content with iframe' => [
        'Lorem ipsum dolor sit amet<iframe src="http://example.com"></iframe><p>Consectetur adipiscing elit</p>Ut finibus vulputate fringilla.',
        '<iframe src="http://example.com"></iframe>',
      ],
      'everything' => [
        'Lorem ipsum dolor sit amet<iframe src="http://example.com" width="800" height="600" allowFullScreen="true">Please enable iframes in your browser.</iframe><p>Consectetur adipiscing elit</p>Ut finibus vulputate fringilla.',
        '<iframe src="http://example.com" width="800" height="600" allowfullscreen="true">Please enable iframes in your browser.</iframe>',
      ],
      'no iframe' => [
        '<a href="#">Lorem ipsum dolor sit amet</a>, consectetur adipiscing elit.',
        '',
      ],
      'iframe wrapped in HTML comment' => [
        '<!--<iframe src="http://example.com/commented"></iframe>-->Lorem ipsum dolor sit amet.',
        '',
      ],
    ];
  }

}
