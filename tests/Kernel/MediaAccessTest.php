<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Kernel;

use Drupal\media\Entity\Media;
use Drupal\Tests\media\Kernel\MediaKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Tests custom media access.
 */
class MediaAccessTest extends MediaKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
    'oe_media',
  ];

  /**
   * The currently logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp();

    $this->installConfig(['oe_media']);

    $this->currentUser = $this->setUpCurrentUser(['username' => 'test'], ['access media overview', 'view media']);
  }

  /**
   * Tests access to unpublished media.
   */
  public function testUnpublishedMedia(): void {
    $media = Media::create([
      'bundle' => $this->testMediaType->id(),
      'name' => 'Test',
    ]);
    $media->save();

    $view = Views::getView('media');
    $this->executeView($view, 'media_page_list');

    $access_handler = $this->container->get('entity_type.manager')->getAccessControlHandler('media');

    // The current user should have access to the published media.
    $this->assertTrue($media->access('view', $this->currentUser));
    $this->assertCount(1, $view->result);

    $media->set('status', 0);
    $media->save();

    // Reset the static cache only.
    $access_handler->resetCache();

    // The current user should not have access to the unpublished media.
    $this->assertFalse($media->access('view', $this->currentUser));

    // The view filter responsible for the media status is introduced only in
    // Drupal 8.8.
    if (version_compare(\Drupal::VERSION, '8.8.0', '>=')) {
      $view = Views::getView('media');
      $this->executeView($view, 'media_page_list');
      $this->assertCount(0, $view->result);
    }

    // Give the user the role to view any unpublished media.
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load($this->currentUser->getRoles(TRUE)[0]);
    $role->grantPermission('view any unpublished media');
    $role->save();

    $access_handler->resetCache();
    // The user should have access to the unpublished media based on the
    // permission.
    $this->assertTrue($media->access('view', $this->currentUser));

    if (version_compare(\Drupal::VERSION, '8.8.0', '>=')) {
      // Again, the View filter is introduced in 8.8 so only then does the new
      // permission take effect on the filter.
      $view = Views::getView('media');
      $this->executeView($view, 'media_page_list');
      $this->assertCount(1, $view->result);
    }
  }

  /**
   * Executes a View.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to execute.
   * @param string $display_id
   *   The display id.
   */
  protected function executeView(ViewExecutable $view, string $display_id): void {
    $view->setDisplay($display_id);
    $view->initQuery();
    $view->preExecute();
    $view->execute();
  }

}
