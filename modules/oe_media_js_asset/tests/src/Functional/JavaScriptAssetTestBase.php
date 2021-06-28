<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_js_asset\Functional;

use Drupal\Tests\media\Functional\MediaFunctionalTestBase;

/**
 * JavaScript asset media test base class.
 *
 * @group oe_media_js_asset
 */
class JavaScriptAssetTestBase extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_media_js_asset',
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

    // Configure values for the environments config.
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
  }

}
