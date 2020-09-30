<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_iframe\Kernel;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\oe_media\Traits\MediaTypeCreationTrait;

/**
 * Tests the field iframe formatter.
 */
class IframeFormatterTest extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'text',
    'system',
    'options',
    'filter',
    'user',
    'image',
    'file',
    'media',
    'oe_media',
    'oe_media_iframe',
  ];

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * The media type.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $mediaType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig([
      'field',
      'filter',
      'media',
      'options',
      'system',
      'user',
      'oe_media',
      'oe_media_iframe',
    ]);
    \Drupal::service('router.builder')->rebuild();
    $this->installEntitySchema('media');
    $this->installEntitySchema('user');

    $media_type = $this->createMediaType('oe_media_iframe', [
      'id' => 'test_iframe',
      'label' => 'Test iframe source',
      'source' => 'oe_media_iframe',
    ]);
    $view_display = \Drupal::service('entity_display.repository')->getViewDisplay('media', $media_type->id());
    $source = $media_type->getSource();
    $source->prepareViewDisplay($media_type, $view_display);
    $view_display->save();
    $this->display = $view_display;

    $this->fieldName = $source->getConfiguration()['source_field'];
    $this->mediaType = $media_type;
  }

  /**
   * Tests iframe formatter output.
   */
  public function testIframeFormatter(): void {
    $value = '<iframe src="http://web:8080/tests/fixtures/example.html" invalid-attribute="with value" width="800" height="600" frameborder="0" name="my-iframe">Iframes not available.</iframe><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit</p>';

    $entity = \Drupal::entityTypeManager()->getStorage('media')->create([
      'bundle' => $this->mediaType->id(),
      'name' => 'Test iframe media',
      'status' => TRUE,
    ]);
    $entity->{$this->fieldName}->value = $value;

    // Verify that the oe_media_iframe format has been applied to the output.
    $this->renderEntityFields($entity, $this->display);
    $this->assertNoRaw($value);
    $this->assertRaw('<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0">Iframes not available.</iframe>');

    // Change the media source text format to plain_text.
    $configuration = $this->mediaType->get('source_configuration');
    $configuration['text_format'] = 'plain_text';
    $this->mediaType->set('source_configuration', $configuration)->save();

    // Verify that the newly selected format is now applied to the output of
    // the formatter.
    $this->renderEntityFields($entity, $this->display);
    $this->assertNoRaw($value);
    $this->assertRaw('<p>&lt;iframe src=&quot;<a href="http://web:8080/tests/fixtures/example.html&amp;quot">http://web:8080/tests/fixtures/example.html&amp;quot</a>; invalid-attribute=&quot;with value&quot; width=&quot;800&quot; height=&quot;600&quot; frameborder=&quot;0&quot; name=&quot;my-iframe&quot;&gt;Iframes not available.&lt;/iframe&gt;&lt;p&gt;Lorem ipsum dolor sit amet, consectetur adipiscing elit&lt;/p&gt;</p>');
  }

  /**
   * Renders fields of a given entity with a given display.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity object with attached fields to render.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display to render the fields in.
   */
  protected function renderEntityFields(FieldableEntityInterface $entity, EntityViewDisplayInterface $display): void {
    $content = $display->build($entity);
    $this->render($content);
  }

}
