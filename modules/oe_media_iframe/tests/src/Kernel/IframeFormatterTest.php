<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_iframe\Kernel;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\MediaType;

/**
 * Tests the field iframe formatter.
 */
class IframeFormatterTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
      'system',
      'field',
      'options',
      'user',
      'media',
      'oe_media',
      'oe_media_iframe',
    ]);
    \Drupal::service('router.builder')->rebuild();
    $this->installEntitySchema('media');
    $this->installEntitySchema('user');

    /** @var \Drupal\media\MediaTypeInterface $media_type */
    $media_type = MediaType::create([
      'id' => 'test_iframe',
      'label' => 'Test iframe source',
      'source' => 'oe_media_iframe',
    ]);
    $media_type->save();
    $source = $media_type->getSource();
    $source_field = $source->createSourceField($media_type);
    $source_configuration = $source->getConfiguration();
    $source_configuration['source_field'] = $source_field->getName();
    $source->setConfiguration($source_configuration);
    $source_field->getFieldStorageDefinition()->save();
    $source_field->save();
    $media_type->set('source_configuration', [
      'source_field' => $source_field->getName(),
    ]);
    $media_type->save();
    $view_display = \Drupal::service('entity_display.repository')
      ->getViewDisplay('media', $media_type->id());
    $source->prepareViewDisplay($media_type, $view_display);
    $view_display->save();
    $this->display = $view_display;

    $this->fieldName = $source_field->getName();
    $this->mediaType = $media_type;
    $this->entityTypeManager = \Drupal::entityTypeManager();
  }

  /**
   * Tests iframe formatter output.
   */
  public function testIframeFormatter(): void {
    $value = '<iframe src="http://web:8080/tests/fixtures/example.html" invalid-attribute="with value" width="800" height="600" frameborder="0" allow allowfullscreen allowpaymentrequest csp importance loading name referrerpolicy sandbox srcdoc mozallowfullscreen webkitAllowFullScreen scrolling accesskey autocapitalize class contenteditable data-test data-test2 dir draggable dropzone exportparts hidden id inputmode is itemid itemprop itemref itemscope itemtype lang part slot spellcheck style tabindex title translate><a href="#">invalid</a></iframe><script type="text/javascript">alert(\'no js\')</script>';

    $entity = $this->entityTypeManager->getStorage('media')->create([
      'bundle' => $this->mediaType->id(),
      'name' => 'Test iframe media',
      'status' => TRUE,
    ]);
    $entity->{$this->fieldName}->value = $value;

    // Verify that all allowed attributes is present and disallowed is removed.
    $this->renderEntityFields($entity, $this->display);
    $this->assertNoRaw($value);
    $this->assertRaw('<iframe src="http://web:8080/tests/fixtures/example.html" width="800" height="600" frameborder="0" allowfullscreen="" importance="" loading="" name="" referrerpolicy="" sandbox="" mozallowfullscreen="" webkitallowfullscreen="" scrolling="" lang="" id="" xml:lang="">invalid</iframe>alert(\'no js\')');
  }

  /**
   * Renders fields of a given entity with a given display.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity object with attached fields to render.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display to render the fields in.
   *
   * @return string
   *   The rendered entity fields.
   */
  protected function renderEntityFields(FieldableEntityInterface $entity, EntityViewDisplayInterface $display): string {
    $content = $display->build($entity);
    $content = $this->render($content);
    return $content;
  }

}