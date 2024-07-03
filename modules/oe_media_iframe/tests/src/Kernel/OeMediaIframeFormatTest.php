<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media_iframe\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the oe_media_iframe filter format.
 *
 * @group oe_media_iframe
 */
class OeMediaIframeFormatTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'filter',
    'image',
    'media',
    'options',
    'system',
    'oe_media',
    'oe_media_iframe',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['oe_media_iframe']);
  }

  /**
   * Tests the oe_media_iframe format configuration.
   *
   * The data provider is used inside the test method to cut execution time.
   */
  public function testFormat(): void {
    foreach ($this->providerTestFormat() as $scenario => $data) {
      $build = [
        '#type' => 'processed_text',
        '#text' => $data['html'],
        '#format' => 'oe_media_iframe',
      ];
      $this->render($build);

      $this->assertRaw($data['expected'], sprintf('Raw text not found on scenario "%s".', $scenario));
    }
  }

  /**
   * Data provider for the testFormat() method.
   *
   * @return array
   *   A list of scenarios.
   */
  protected function providerTestFormat(): array {
    return [
      'complex HTML' => [
        'html' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0"><a href="#">invalid</a></iframe><script type="text/javascript">alert(\'no js\')</script><p>Lorem</p><div>ipsum</div><strong>dolor</strong><em>sit</em>amet, consectetur adipiscing elit',
        'expected' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0">invalid</iframe>',
      ],
      'EbS Live embed code' => [
        'html' => '<iframe src="http://web:8080/tests/fixtures/example.html" id="videoplayer" width="852" height="480" title="" frameborder="0" scrolling="no" webkitAllowFullScreen="true" mozallowfullscreen="true" allowFullScreen="true"></iframe>',
        'expected' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="852" height="480" title frameborder="0" scrolling="no" webkitallowfullscreen="true" mozallowfullscreen="true" allowfullscreen="true"></iframe>',
      ],
      'iframe with all existing iframe attributes' => [
        // Lang and dir attributes are always allowed.
        'html' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0" allow allowfullscreen allowpaymentrequest csp importance loading referrerpolicy sandbox srcdoc mozallowfullscreen webkitAllowFullScreen scrolling accesskey autocapitalize class contenteditable data-test data-test2 dir draggable dropzone exportparts hidden id inputmode is itemid itemprop itemref itemscope itemtype lang part slot spellcheck style tabindex title translate></iframe>',
        'expected' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0" allowfullscreen importance loading referrerpolicy sandbox mozallowfullscreen webkitallowfullscreen scrolling lang title></iframe>',
      ],
      'iframe with invalid attribute' => [
        'html' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0" invalid-attribute="with random value"></iframe>',
        'expected' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0"></iframe>',
      ],
    ];
  }

}
