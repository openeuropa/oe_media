<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\FunctionalJavascript;

/**
 * Tests the image widgets of the media entity browser.
 *
 * @group oe_media
 * @group batch3
 */
class ImageEntityBrowserTest extends MediaFeatureTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'access content',
      'create oe_media_demo content',
      'create image media',
      'access media_entity_browser entity browser pages',
      'view media',
    ]));
  }

  /**
   * Tests that images can be added and referenced through the entity browser.
   */
  public function testImageEntityBrowser(): void {
    $assert_session = $this->assertSession();
    $media_name = 'OpenEuropa team members at Symfonycon Lisbon';

    $this->drupalGet('node/add/oe_media_demo');
    $assert_session->pageTextContains('Create OpenEuropa Media Demo');
    $this->getSession()->getPage()->fillField('Title', 'OpenEuropa at SymfonyCon Lisbon');

    $this->openEntityBrowser();
    $this->clickEntityBrowserTab('Add Image');
    $browser_page = $this->getSession()->getPage();
    $browser_page->fillField('Name', $media_name);
    $browser_page->attachFileToField('Image', $this->getFixturePath('example_1.jpeg'));
    $assert_session->assertWaitOnAjaxRequest();
    $browser_page->fillField('Alternative text', 'Symfonycon Lisbon');
    $browser_page->pressButton('Save entity');
    $this->waitForEntityBrowserToClose();

    $this->assertNotEmpty($assert_session->waitForText($media_name));
    $assert_session->buttonExists('Remove');
    $this->getSession()->getPage()->pressButton('Save');
    $assert_session->pageTextContains('OpenEuropa at SymfonyCon Lisbon');
    $this->assertImageRendered('example_1.jpeg');

    // Reuse the existing image media on another node.
    $this->drupalGet('node/add/oe_media_demo');
    $assert_session->pageTextContains('Create OpenEuropa Media Demo');
    $this->getSession()->getPage()->fillField('Title', 'OpenEuropa around Europe');

    $this->openEntityBrowser();
    $this->selectMediaInEntityBrowser($media_name);
    $this->getSession()->getPage()->pressButton('Select entities');
    $this->waitForEntityBrowserToClose();

    $this->assertNotEmpty($assert_session->waitForText($media_name));
    $assert_session->buttonExists('Remove');
    $this->getSession()->getPage()->pressButton('Save');
    $assert_session->pageTextContains('OpenEuropa around Europe');
    $this->assertImageRendered('example_1.jpeg');
  }

}
