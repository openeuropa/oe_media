<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_avportal\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the AV Portal Entity Browser widget.
 */
class AvPortalEntityBrowserWidgetTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'views',
    'oe_media_avportal',
    'media_avportal_mock',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container->get('module_installer')->install(['oe_media_avportal_test']);

    $user = $this->drupalCreateUser([
      'access av_portal_entity_browser_test entity browser pages',
    ]);

    $this->drupalLogin($user);
  }

  /**
   * Tests AV Portal Entity Browser widget that is based on Views.
   */
  public function testWidget(): void {
    // Visit the iframe of the Entity Browser.
    $this->drupalGet('/entity-browser/iframe/av_portal_entity_browser_test');

    // Assert the search field.
    $this->assertSession()->fieldExists('search');

    // Assert the pager.
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertCount(4, $elements);

    $entity_type_manager = $this->container->get('entity_type.manager');
    $media_title = 'LIVE "Subsidiarity - as a building principle of the European Union" Conference in Bregenz, Austria - Welcome, keynote speech and interviews';

    // Make a selection and make sure the entity gets created.
    $this->assertEmpty($entity_type_manager->getStorage('media')->loadMultiple());
    $this->getSession()->getPage()->checkField('entity_browser_select[I-163308]');
    $this->getSession()->getPage()->pressButton('Select entities');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSingleMediaEntity($media_title);

    // Make the same selection again and make sure the entity gets reused.
    $this->drupalGet('/entity-browser/iframe/av_portal_entity_browser_test');
    $this->getSession()->getPage()->checkField('entity_browser_select[I-163308]');
    $this->getSession()->getPage()->pressButton('Select entities');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSingleMediaEntity($media_title);
  }

  /**
   * Asserts that only a single Media entity with the given title was created.
   *
   * @param string $title
   *   The media title.
   */
  protected function assertSingleMediaEntity(string $title): void {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entities = $entity_type_manager->getStorage('media')->loadMultiple();
    $this->assertCount(1, $entities);
    $media = reset($entities);
    $this->assertEquals($title, trim($media->label()));
  }

}
