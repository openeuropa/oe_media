<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\oe_media_avportal\Plugin\views\query\AVPortalQuery;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Tests a view created for the AV Portal data.
 */
class AvPortalViewsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
    'entity_browser',
    'system',
    'media_avportal',
    'media_avportal_mock',
    'oe_media_avportal',
    'oe_media_avportal_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp();

    $this->installConfig([
      'system',
      'entity_browser',
      'media_avportal',
      'oe_media_avportal_test',
    ]);
  }

  /**
   * Tests the View that uses the AV Portal query plugin.
   */
  public function testDefaultAvPortalViews(): void {
    $view = Views::getView('av_portal');
    $this->executeView($view);
    $this->assertInstanceOf(AVPortalQuery::class, $view->query, 'Wrong query plugin used in the view.');

    // By default, the view should show 10 results.
    $this->assertCount(10, $view->result);

    // Assert the first result from the mock.
    $row = $view->result[0];
    $this->assertEquals('I-163595', $row->ref);
    $this->assertEquals(' ', $row->title);
    $this->assertEquals('http://defiris.ec.streamcloud.be/findmedia/15/163595/THUMB_M_I163595INT1W_6.jpg', $row->thumbnail);

    // Assert the second result from the mock.
    $row = $view->result[1];
    $this->assertEquals('I-163308', $row->ref);
    $this->assertEquals(' LIVE "Subsidiarity - as a building principle of the European Union" Conference in Bregenz, Austria - Welcome, keynote speech and interviews', $row->title);
    $this->assertContains('media/images/icons/no-thumbnail.png', $row->thumbnail);

    // Assert that it works correctly with the pager.
    $view = Views::getView('av_portal');
    $view->setCurrentPage(1);
    $this->executeView($view);

    $this->assertCount(5, $view->result);
    $row = $view->result[0];
    $this->assertEquals('I-163665', $row->ref);
    $this->assertEquals(' STOCKSHOTS EP', $row->title);

    // Assert that we can filter using the custom search text filter.
    $view = Views::getView('av_portal');
    $view->setExposedInput(['search' => 'europe']);
    $this->executeView($view);

    $this->assertCount(10, $view->result);
    $row = $view->result[0];
    $this->assertEquals('I-163675', $row->ref);
    $this->assertEquals(' Start-up Europe Award Ceremony', $row->title);
  }

  /**
   * Executes a View.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to execute.
   */
  protected function executeView(ViewExecutable $view) {
    $view->setDisplay();
    $view->initQuery();
    $view->preExecute();
    $view->execute();
  }

}
