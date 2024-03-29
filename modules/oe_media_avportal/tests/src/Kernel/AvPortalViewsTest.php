<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\oe_media_avportal\Plugin\views\query\AVPortalQuery;
use Drupal\views\Entity\View;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Tests a view created for the AV Portal data.
 */
class AvPortalViewsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'user',
    'image',
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
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installConfig(['media']);

    $this->installConfig([
      'image',
      'system',
      'entity_browser',
      'media_avportal',
      'oe_media_avportal_test',
    ]);
  }

  /**
   * Tests the View that uses the AV Portal query plugin with video.
   */
  public function testDefaultAvPortalVideoViews(): void {
    $view = Views::getView('av_portal_test');
    $this->executeView($view, 'page_1');
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
    $this->assertStringContainsString('media/images/icons/no-thumbnail.png', $row->thumbnail);

    // Assert that it works correctly with the pager.
    $view = Views::getView('av_portal_test');
    $view->setCurrentPage(1);
    $this->executeView($view, 'page_1');

    $this->assertCount(5, $view->result);
    $row = $view->result[0];
    $this->assertEquals('I-163665', $row->ref);
    $this->assertEquals(' STOCKSHOTS EP', $row->title);

    // Assert that we can filter using the custom search text filter.
    $view = Views::getView('av_portal_test');
    $view->setExposedInput(['search' => 'europe']);
    $this->executeView($view, 'page_1');

    $this->assertCount(10, $view->result);
    $row = $view->result[0];
    $this->assertEquals('I-163675', $row->ref);
    $this->assertEquals(' Start-up Europe Award Ceremony', $row->title);
  }

  /**
   * Tests the View that uses the AV Portal query plugin with photo.
   */
  public function testDefaulAvPortalPhotoViews(): void {
    $view = Views::getView('av_portal_test');
    $this->executeView($view, 'page_2');
    $this->assertInstanceOf(AVPortalQuery::class, $view->query, 'Wrong query plugin used in the view.');

    // By default, the view should show 10 results.
    $this->assertCount(10, $view->result);

    // Assert the first result from the mock.
    $row = $view->result[0];
    $this->assertEquals('P-039321/00-04', $row->ref);
    $this->assertEquals('Visit by Federica Mogherini, Vice-President of the EC, and Johannes Hahn, Member of the EC, to Romania', $row->title);
    $this->assertEquals('https://ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/1/P039321-225309.jpg', $row->thumbnail);

    // Assert the second result from the mock.
    $row = $view->result[1];
    $this->assertEquals('P-039321/00-05', $row->ref);
    $this->assertEquals('Visit by Federica Mogherini, Vice-President of the EC, and Johannes Hahn, Member of the EC, to Romania', $row->title);
    $this->assertStringContainsString('//ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/1/P039321-937559.jpg', $row->thumbnail);

    // Assert that it works correctly with the pager.
    $view = Views::getView('av_portal_test');
    $view->setCurrentPage(1);
    $this->executeView($view, 'page_2');

    $this->assertCount(5, $view->result);
    $row = $view->result[0];
    $this->assertEquals('P-039321/00-09', $row->ref);
    $this->assertEquals('Visit by Federica Mogherini, Vice-President of the EC, and Johannes Hahn, Member of the EC, to Bulgaria', $row->title);

    // Assert that we can filter using the custom search text filter.
    $view = Views::getView('av_portal_test');
    $view->setExposedInput(['search' => 'europe']);
    $this->executeView($view, 'page_2');

    $this->assertCount(10, $view->result);
    $row = $view->result[0];
    $this->assertEquals('P-039300/00-05', $row->ref);
    $this->assertEquals('Participation of Jean-Claude Juncker, President of the EC, at debate on the future of Europe, with Members of the European Parliament ', $row->title);
  }

  /**
   * Tests the View that uses the AV Portal query plugin with both types.
   */
  public function testDefaultAvPortalBothViews(): void {
    $view = Views::getView('av_portal_test');
    $this->executeView($view, 'page_3');
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
    $this->assertStringContainsString('media/images/icons/no-thumbnail.png', $row->thumbnail);

    // Assert that it works correctly with the pager.
    $view = Views::getView('av_portal_test');
    $view->setCurrentPage(1);
    $this->executeView($view, 'page_3');

    $this->assertCount(5, $view->result);
    $row = $view->result[0];
    $this->assertEquals('I-163665', $row->ref);
    $this->assertEquals(' STOCKSHOTS EP', $row->title);

    // Assert that we can filter using the custom search text filter.
    $view = Views::getView('av_portal_test');
    $view->setExposedInput(['search' => 'europe']);
    $this->executeView($view, 'page_3');

    $this->assertCount(10, $view->result);
    $row = $view->result[0];
    $this->assertEquals('I-163675', $row->ref);
    $this->assertEquals(' Start-up Europe Award Ceremony', $row->title);
  }

  /**
   * Tests the query response caching capabilities.
   */
  public function testAvPortalQueryCache(): void {
    $history_middleware = $this->container->get('media_avportal_mock.history_middleware');

    // The test view has the query caching disabled.
    $view = Views::getView('av_portal_test');
    $this->executeView($view, 'page_3');
    // One HTTP request has been fired to the AV Portal service.
    $this->assertCount(1, $history_middleware->getHistoryContainer());

    // Execute again the view. A new HTTP request should be made.
    $view = Views::getView('av_portal_test');
    $this->executeView($view, 'page_3');
    $this->assertCount(2, $history_middleware->getHistoryContainer());

    // Enable cache for AV Portal queries.
    $view = View::load('av_portal_test');
    $display = &$view->getDisplay('default');
    $display['display_options']['query']['options']['cache_query_response'] = TRUE;
    $view->save();

    $view = Views::getView('av_portal_test');
    $this->executeView($view, 'page_3');
    $this->assertCount(3, $history_middleware->getHistoryContainer());

    // Executing again the view will hit the cache.
    $view = Views::getView('av_portal_test');
    $this->executeView($view, 'page_3');
    $this->assertCount(3, $history_middleware->getHistoryContainer());

    // Requesting a different page causes a new HTTP request.
    $view = Views::getView('av_portal_test');
    $view->setCurrentPage(2);
    $this->executeView($view, 'page_3');
    $this->assertCount(4, $history_middleware->getHistoryContainer());
  }

  /**
   * Executes a View.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to execute.
   * @param string $display_id
   *   The display id.
   *
   * @internal param string $display The display id*   The display id
   */
  protected function executeView(ViewExecutable $view, string $display_id): void {
    $view->setDisplay($display_id);
    $view->initQuery();
    $view->preExecute();
    $view->execute();
  }

}
