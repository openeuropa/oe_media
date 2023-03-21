<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_iframe\Functional;

use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\Tests\oe_media\Traits\MediaTypeCreationTrait;

/**
 * Tests the iframe widget.
 *
 * @group oe_media_iframe
 */
class IframeWidgetTest extends MediaFunctionalTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_media_iframe',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createMediaType('oe_media_iframe', [
      'id' => 'test_iframe',
      'label' => 'Test iframe source',
      'source' => 'oe_media_iframe',
      'source_configuration' => [
        'text_format' => 'plain_text',
      ],
    ]);
  }

  /**
   * Tests the widget form element.
   */
  public function testWidgetElements(): void {
    $this->drupalGet('/media/add/test_iframe');

    // Verify that the filter tips are present just after the iframe field.
    $tips = $this->getSession()->getPage()->findAll('css', '.form-item-field-media-oe-media-iframe-0-value + ul > li');
    $this->assertCount(3, $tips);
    $this->assertEquals('No HTML tags allowed.', $tips[0]->getText());
    $this->assertEquals('Lines and paragraphs break automatically.', $tips[1]->getText());
    $this->assertEquals('Web page addresses and email addresses turn into links automatically.', $tips[2]->getText());
    $this->assertEquals('textarea', $this->assertSession()->fieldExists('Iframe')->getTagName());
  }

}
