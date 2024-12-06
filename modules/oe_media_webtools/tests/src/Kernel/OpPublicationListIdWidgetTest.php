<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media_webtools\Kernel;

use Drupal\Tests\oe_media\Kernel\MediaTestBase;
use Drupal\oe_media_webtools\Plugin\Field\FieldWidget\OpPublicationListIdWidget;

/**
 * Tests the 'OP Publication List ID' field widget.
 */
class OpPublicationListIdWidgetTest extends MediaTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'json_field',
    'media',
    'oe_webtools',
    'oe_webtools_media',
    'oe_media_webtools',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig([
      'json_field',
      'oe_media_webtools',
      'oe_webtools_media',
    ]);
  }

  /**
   * @covers ::isApplicable
   */
  public function testIsApplicable() {
    $fields = $this->container->get('entity_field.manager')->getFieldDefinitions('media', 'webtools_op_publication_list');
    $this->assertTrue(OpPublicationListIdWidget::isApplicable($fields['oe_media_webtools']));

    $fields = $this->container->get('entity_field.manager')->getFieldDefinitions('media', 'webtools_map');
    $this->assertFalse(OpPublicationListIdWidget::isApplicable($fields['oe_media_webtools']));
  }

}
