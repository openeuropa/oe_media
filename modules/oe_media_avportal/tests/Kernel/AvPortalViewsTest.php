<?php

namespace Drupal\Tests\oe_media\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\oe_media_avportal\Plugin\views\query\AVPortalQuery;
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

    $this->installConfig(['system', 'media_avportal', 'oe_media_avportal_test']);
  }

  /**
   * Tests a View that uses the AV Portal related plugins.
   */
  public function testDefaultAvPortalViews() {
    $view = Views::getView('av_portal');
    $view->setDisplay();
    $view->initQuery();
    $this->assertInstanceOf(AVPortalQuery::class, $view->query, 'Wrong query plugin used in the view.');

    // Execute the view.
    $view->preExecute();
    $view->execute();

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
  }

}
