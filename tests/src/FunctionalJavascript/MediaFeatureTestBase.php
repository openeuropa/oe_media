<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\oe_media\Traits\EntityBrowserTrait;
use Drupal\Tests\oe_media\Traits\MediaAssertionsTrait;
use Drupal\Tests\oe_media\Traits\MediaTestTrait;
use OpenEuropa\TestingUtilities\Traits\CachedDatabaseInstallTrait;

/**
 * Base class for the media feature tests.
 *
 * The module list is deliberately identical to the one of
 * \Drupal\Tests\oe_media\Functional\MediaFeatureTestBase so that both
 * hierarchies share a single cached database install. Keep them in sync.
 */
abstract class MediaFeatureTestBase extends WebDriverTestBase {

  use CachedDatabaseInstallTrait;
  use EntityBrowserTrait;
  use MediaAssertionsTrait;
  use MediaTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'file',
    'file_link',
    'image',
    'link',
    'options',
    'views',
    'media',
    'oe_media',
    'oe_media_demo',
    'media_avportal_mock',
    'oe_media_oembed_mock',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->cacheDbInstall = TRUE;
    parent::setUp();
  }

  /**
   * Gives media entities a canonical URL of their own.
   */
  protected function enableMediaStandaloneUrl(): void {
    $this->config('media.settings')->set('standalone_url', TRUE)->save();
    $this->container->get('router.builder')->rebuild();
  }

}
