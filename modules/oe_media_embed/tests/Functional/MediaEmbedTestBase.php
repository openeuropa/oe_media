<?php

namespace Drupal\Tests\oe_media_embed\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\oe_media_embed\Traits\MediaEmbedTrait;

/**
 * Base class for all media embed tests.
 */
abstract class MediaEmbedTestBase extends BrowserTestBase {

  use MediaEmbedTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'embed',
    'oe_media',
    'oe_media_embed',
    'node',
    'ckeditor',
  ];

  /**
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->basicSetup();
  }
}
