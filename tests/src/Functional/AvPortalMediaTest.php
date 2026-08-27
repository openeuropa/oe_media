<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\Functional;

/**
 * Tests the creation and the referencing of AV Portal media entities.
 *
 * @group oe_media
 * @group batch3
 */
class AvPortalMediaTest extends MediaFeatureTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([], '', TRUE));
  }

  /**
   * Tests AV Portal Entity Browser widget that is based on Views.
   */
  public function testAvPortalWidget(): void {
    \Drupal::service('module_installer')->install(['oe_media_avportal_test']);

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
    $this->assertSingleMediaEntity($media_title);

    // Make the same selection again and make sure the entity gets reused.
    $this->drupalGet('/entity-browser/iframe/av_portal_entity_browser_test');
    $this->getSession()->getPage()->checkField('entity_browser_select[I-163308]');
    $this->getSession()->getPage()->pressButton('Select entities');
    $this->assertSingleMediaEntity($media_title);
  }

  /**
   * Tests that an AV Portal photo/video can be created and ref'ed in a node.
   */
  public function testAvPortalPhotoAndVideo(): void {
    $page = $this->getSession()->getPage();

    // The photo.
    $this->drupalGet('media/add/av_portal_photo');
    $page->fillField('Media AV Portal Photo', 'https://audiovisual.ec.europa.eu/en/photo/P-038924~2F00-15');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('AV Portal Photo Euro with miniature figurines has been created.');

    $this->drupalGet('node/add/oe_media_demo');
    $page->fillField('Title', 'My photo demo node');
    $page->fillField('field_oe_demo_av_portal_photo[0][target_id]', 'Euro with miniature figurines');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('My photo demo node');
    $this->assertAvPortalPhotoRendered('Euro with miniature figurines', '//ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/4/P038924-352937.jpg');

    // The video.
    $this->drupalGet('media/add/av_portal_video');
    $page->fillField('Media AV Portal Video', 'https://audiovisual.ec.europa.eu/en/video/I-162747');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('AV Portal Video Midday press briefing from 25/10/2018 has been created.');

    $this->drupalGet('node/add/oe_media_demo');
    $page->fillField('Title', 'My video demo node');
    $page->fillField('field_oe_demo_av_portal_video[0][target_id]', 'Midday press briefing from 25/10/2018');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('My video demo node');
    $this->assertAvPortalVideoRendered('Midday press briefing from 25/10/2018');
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
