<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media_js_asset\Kernel;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\oe_media\Traits\MediaTypeCreationTrait;

/**
 * Tests the field JS asset URL formatter.
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
    $source = $media_type->getSource();
    $this->fieldName = $source->getConfiguration()['source_field'];
  }

  /**
   * Tests JavaScript asset URL formatter output.
   */
  public function testJavaScriptAssetUrlFormatter(): void {
    $environments = [
      'acceptance' => [
        'label' => 'Acceptance',
        'url' => 'https://acceptance.europa.eu/webassets',
      ],
      'production' => [
        'label' => 'Production',
        'url' => 'https://europa.eu/webassets',
      ],
    ];
    $config = \Drupal::configFactory()->getEditable('oe_media_js_asset.settings');
    $config->set('environments', $environments)->save();

    $media_storage = \Drupal::entityTypeManager()->getStorage('media');
    $entity = $media_storage->create([
      'bundle' => 'test_js_asset',
      'name' => 'Test JS asset media',
      'status' => TRUE,
    ]);
    $entity->{$this->fieldName}->environment = 'environment';
    $entity->{$this->fieldName}->path = '/somejavascript.js';

    $view_display = \Drupal::service('entity_display.repository')->getViewDisplay('media', 'test_js_asset');
    $this->renderEntityFields($entity, $view_display);
    // The string should not be present because the given environment doesn't
    // exists in the config.
    $this->assertNoRaw('/somejavascript.js');

    $entity->{$this->fieldName}->environment = 'acceptance';
    $this->renderEntityFields($entity, $view_display);
    $this->assertRaw('<script src="https://acceptance.europa.eu/webassets/somejavascript.js"></script>');

    // Assert multiple JS assets.
    $entity->{$this->fieldName} = [
      [
        'environment' => 'acceptance',
        'path' => '/somejavascript.js',
      ],
      [
        'environment' => 'production',
        'path' => '/somejavascript2.js',
      ],
    ];
    $this->renderEntityFields($entity, $view_display);
    $this->assertRaw('<script src="https://acceptance.europa.eu/webassets/somejavascript.js"></script>');
    $this->assertRaw('<script src="https://europa.eu/webassets/somejavascript2.js"></script>');
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
