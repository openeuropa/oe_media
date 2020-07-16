<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_iframe\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\media\Functional\MediaFunctionalTestBase;

/**
 * Test the Media Iframe text formats.
 *
 * @group oe_media_iframe
 */
class MediaIframeFormatTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_media_iframe',
    'oe_media_demo',
  ];

  /**
   * Test text formats selected for Media source.
   */
  public function testMediaSourceTextFormats(): void {
    foreach ($this->getFixtures() as $test_data) {
      $media = $this->storage->create([
        'bundle' => 'video_iframe',
        'name' => 'Test iframe media',
        'oe_media_iframe' => $test_data['html'],
        'status' => TRUE,
      ]);
      $media->save();

      $node = Node::create([
        'type' => 'oe_media_demo',
        'title' => 'Test Demo page',
        'field_oe_demo_video_iframe' => [
            [
              'target_id' => $media->id(),
            ],
        ],
        'status' => TRUE,
      ]
      );
      $node->save();

      foreach ($test_data['formats_output'] as $format_name => $expected) {
        $this->drupalLogin($this->adminUser);
        $this->drupalGet('admin/structure/media/manage/video_iframe');
        $this->getSession()->getPage()->selectFieldOption('Text format', $format_name);
        $this->getSession()->getPage()->pressButton('Save');
        $this->drupalLogout();
        $this->drupalGet('node/' . $node->id());
        $this->assertSession()->responseContains($expected);
      }
    }

  }

  /**
   * Array of data for testing text formats.
   *
   * @return array
   *   The test data.
   */
  protected function getFixtures(): array {
    return [
      'test standard iframe with 2 formats' => [
        'html' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0"><a href="#">invalid</a></iframe><script type="text/javascript">alert(\'no js\')</script>',
        'formats_output' => [
          'oe_media_iframe' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0">invalid</iframe>alert(\'no js\')',
          'plain_text' => '&lt;iframe src=&quot;<a href="http://web:8080/tests/fixtures/example.html&amp;quot">http://web:8080/tests/fixtures/example.html&amp;quot</a>; width=&quot;800&quot; height=&quot;600&quot; frameborder=&quot;0&quot;&gt;&lt;a href=&quot;#&quot;&gt;invalid&lt;/a&gt;&lt;/iframe&gt;&lt;script type=&quot;text/javascript&quot;&gt;alert(&#039;no js&#039;)&lt;/script&gt;',
        ],
      ],
      'test iframe with used attributes for EbS Live' => [
        'html' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0" allow allowfullscreen allowpaymentrequest csp importance loading name referrerpolicy sandbox srcdoc mozallowfullscreen webkitAllowFullScreen scrolling><a href="#">invalid</a></iframe><script type="text/javascript">alert(\'no js\')</script>',
        'formats_output' => [
          'oe_media_iframe' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0" allow="" allowfullscreen="" allowpaymentrequest="" csp="" importance="" loading="" name="" referrerpolicy="" sandbox="" srcdoc="" mozallowfullscreen="" webkitallowfullscreen="" scrolling="" id="">invalid</iframe>alert(\'no js\')',
        ],
      ],
      'test iframe with all allowed attributes' => [
        'html' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0" allow allowfullscreen allowpaymentrequest csp importance loading name referrerpolicy sandbox srcdoc mozallowfullscreen webkitAllowFullScreen scrolling accesskey autocapitalize class contenteditable data-test data-test2 dir draggable dropzone exportparts hidden id inputmode is itemid itemprop itemref itemscope itemtype lang part slot spellcheck style tabindex title translate><a href="#">invalid</a></iframe><script type="text/javascript">alert(\'no js\')</script>',
        'formats_output' => [
          'oe_media_iframe' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0" allow="" allowfullscreen="" allowpaymentrequest="" csp="" importance="" loading="" name="" referrerpolicy="" sandbox="" srcdoc="" mozallowfullscreen="" webkitallowfullscreen="" scrolling="" accesskey="" autocapitalize="" class="" contenteditable="" data-test="" data-test2="" draggable="" dropzone="" exportparts="" hidden="" id="" inputmode="" is="" itemid="" itemprop="" itemref="" itemscope="" itemtype="" lang="" part="" slot="" spellcheck="" tabindex="" title="" translate="" xml:lang="">invalid</iframe>alert(\'no js\')',
        ],
      ],
      'test iframe with not allowed attribute' => [
        'html' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0" invalid-attribute="with random value"></iframe>',
        'formats_output' => [
          'oe_media_iframe' => '<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0"></iframe>',
        ],
      ],
    ];
  }

}
