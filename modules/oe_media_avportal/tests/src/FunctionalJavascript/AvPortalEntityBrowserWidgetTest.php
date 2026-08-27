<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media_avportal\FunctionalJavascript;

use Drupal\Tests\oe_media\FunctionalJavascript\MediaFeatureTestBase;

/**
 * Tests the AV Portal Entity Browser widgets.
 *
 * @group batch1
 */
class AvPortalEntityBrowserWidgetTest extends MediaFeatureTestBase {

  /**
   * The source of the "Euro with miniature figurines" AV Portal photo.
   */
  protected const EURO_PHOTO_SOURCE = '//ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/4/P038924-352937.jpg';

  /**
   * The source of the "Visit by Federica Mogherini" AV Portal photo.
   */
  protected const MOGHERINI_PHOTO_SOURCE = '//ec.europa.eu/avservices/avs/files/video6/repository/prod/photo/store/store2/1/P039321-615406.jpg';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([], '', TRUE));
  }

  /**
   * Tests the AV Portal photo widgets of the media entity browser.
   */
  public function testAvPortalPhotoWidgets(): void {
    $assert_session = $this->assertSession();

    // A new AV Portal photo can be created from within the entity browser.
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField('Title', 'Media demo');
    $this->openEntityBrowser();
    $this->clickEntityBrowserTab('Add AV Portal Photo');
    $this->getSession()->getPage()->fillField('Media AV Portal Photo', 'https://audiovisual.ec.europa.eu/en/photo/P-038924~2F00-15');
    $this->getSession()->getPage()->pressButton('Save entity');
    $this->waitForEntityBrowserToClose();
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('has been created');
    $this->assertAvPortalPhotoRendered('Euro with miniature figurines', self::EURO_PHOTO_SOURCE);

    // The photo created above can be reused through the View widget, which is
    // the default one of the browser.
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField('Title', 'Media demo');
    $this->openEntityBrowser();
    $this->selectMediaInEntityBrowser('Euro with miniature figurines');
    $this->getSession()->getPage()->pressButton('Select entities');
    $this->waitForEntityBrowserToClose();
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('has been created');
    $this->assertAvPortalPhotoRendered('Euro with miniature figurines', self::EURO_PHOTO_SOURCE);

    // Photos can also be searched directly in AV Portal.
    $title = 'Visit by Federica Mogherini, Vice-President of the EC, and Johannes Hahn, Member of the EC, to Romania';
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField('Title', 'Media demo');
    $this->openEntityBrowser();
    $this->clickEntityBrowserTab('Search photos in AV Portal');
    $assert_session->pageTextContains($title);
    $this->selectEntityBrowserRow('P-039321/00-04');
    $this->getSession()->getPage()->pressButton('Select entities');
    $this->waitForEntityBrowserToClose();
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('has been created');
    $this->assertAvPortalPhotoRendered($title, self::MOGHERINI_PHOTO_SOURCE);
  }

  /**
   * Tests the AV Portal video widgets of the media entity browser.
   */
  public function testAvPortalVideoWidgets(): void {
    $assert_session = $this->assertSession();

    \Drupal::service('module_installer')->install(['oe_media_avportal_test']);

    // An existing video, used below to assert that media can be reused.
    /** @var \Drupal\media_avportal\Plugin\media\Source\MediaAvPortalSourceInterface $source */
    $source = \Drupal::entityTypeManager()->getStorage('media_type')->load('av_portal_video')->getSource();
    $existing = \Drupal::entityTypeManager()->getStorage('media')->create([
      'bundle' => 'av_portal_video',
      'oe_media_avportal_video' => $source->transformUrlToReference('https://audiovisual.ec.europa.eu/en/video/I-163162'),
      'status' => 1,
    ]);
    $existing->save();

    // A new AV Portal video can be created from within the entity browser.
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField('Title', 'Media demo');
    $this->openEntityBrowser();
    $this->clickEntityBrowserTab('Add AV Portal Video');
    $this->getSession()->getPage()->fillField('Media AV Portal Video', 'https://audiovisual.ec.europa.eu/en/video/I-162747');
    $this->getSession()->getPage()->pressButton('Save entity');
    $this->waitForEntityBrowserToClose();
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('has been created');
    $this->assertAvPortalVideoRendered('Midday press briefing from 25/10/2018');

    // The pre-existing video can be reused through the View widget.
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField('Title', 'Media demo');
    $this->openEntityBrowser();
    // The label of this AV Portal resource starts with a space, so select it
    // by ID rather than looking it up by name.
    $this->selectEntityBrowserRow('media:' . $existing->id());
    $this->getSession()->getPage()->pressButton('Select entities');
    $this->waitForEntityBrowserToClose();
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('has been created');
    $this->assertAvPortalVideoReferenceRendered($existing->get('oe_media_avportal_video')->value);

    // The upload widget points editors to the AV Portal service.
    $this->drupalGet('node/add/oe_media_demo');
    $this->openEntityBrowser();
    $this->clickEntityBrowserTab('Register AV Portal video');
    $assert_session->linkExists('external link');
    $this->leaveEntityBrowser();

    // Videos can also be searched directly in AV Portal.
    $title = 'LIVE "Subsidiarity - as a building principle of the European Union"';
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField('Title', 'Media demo');
    $this->openEntityBrowser();
    $this->clickEntityBrowserTab('Search videos in AV Portal');
    $assert_session->pageTextContains($title);
    $this->selectEntityBrowserRow('I-163308');
    $this->getSession()->getPage()->pressButton('Select entities');
    $this->waitForEntityBrowserToClose();
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('has been created');
    $this->assertAvPortalVideoReferenceRendered('I-163308');
  }

}
