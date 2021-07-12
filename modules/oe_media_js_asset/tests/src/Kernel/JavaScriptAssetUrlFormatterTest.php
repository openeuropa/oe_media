<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_js_asset\Kernel;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\oe_media\Traits\MediaTypeCreationTrait;

/**
 * Tests the field JS asset url formatter.
 */
class JavaScriptAssetUrlFormatterTest extends KernelTestBase {

  use MediaTypeCreationTrait;

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
    'text',
    'user',
    'oe_media',
    'oe_media_js_asset',
    'file_link',
    'link',
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
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig([
      'field',
      'filter',
      'media',
      'options',
      'system',
      'user',
      'oe_media',
      'oe_media_js_asset',
    ]);
    \Drupal::service('router.builder')->rebuild();
    $this->installEntitySchema('media');
    $this->installEntitySchema('user');

    $media_type = $this->createMediaType('javascript_asset', [
      'id' => 'test_js_asset',
      'label' => 'Test JS asset',
      'source' => 'oe_media_js_asset',
    ]);

    $view_display = \Drupal::service('entity_display.repository')->getViewDisplay('media', $media_type->id());
    $this->display = $view_display;
    $source = $media_type->getSource();
    $this->fieldName = $source->getConfiguration()['source_field'];
    $this->mediaType = $media_type;
  }

  /**
   * Tests JavaScript asset url formatter output.
   */
  public function testJavaScriptAssetUrlFormatter(): void {
    $environments = [
      'acceptance' => [
        'label' => 'Acceptance',
        'url' => 'https://acceptance.europa.eu/webassets',
      ],
    ];
    $config = \Drupal::configFactory()->getEditable('oe_media_js_asset.settings');
    $config->set('environments', $environments)->save();

    $entity = \Drupal::entityTypeManager()->getStorage('media')->create([
      'bundle' => $this->mediaType->id(),
      'name' => 'Test JS asset media',
      'status' => TRUE,
    ]);
    $entity->{$this->fieldName}->environment = 'acceptance';
    $entity->{$this->fieldName}->path = '/somejavascript.js';

    // Verify that the oe_media_js_asset_url format has been applied.
    $this->renderEntityFields($entity, $this->display);
    $this->assertRaw('<script type="application/json" src="https://acceptance.europa.eu/webassets/somejavascript.js"></script>');
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
