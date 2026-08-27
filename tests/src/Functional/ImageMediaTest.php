<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\Functional;

/**
 * Tests the creation and the referencing of image media entities.
 *
 * @group oe_media
 * @group batch3
 */
class ImageMediaTest extends MediaFeatureTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->enableMediaStandaloneUrl();
    $this->drupalLogin($this->drupalCreateUser([], '', TRUE));
  }

  /**
   * Tests that images can be uploaded and attached to nodes.
   */
  public function testImageUploadAndReference(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('media/add/image');
    $assert_session->pageTextContains('Add Image');
    $page->fillField('Name', 'My Image 1');
    $page->attachFileToField('Image', $this->getFixturePath('example_1.jpeg'));
    $page->pressButton('Upload');
    $page->fillField('Alternative text', 'Image Alt Text 1');
    $page->pressButton('Save');
    $assert_session->pageTextContains('My Image 1');

    $this->drupalGet('node/add/oe_media_demo');
    $assert_session->pageTextContains('Create OpenEuropa Media Demo');
    $page->fillField('Title', 'My Node');
    $page->fillField('field_oe_demo_image_media[0][target_id]', 'My Image 1');
    $page->pressButton('Save');

    $assert_session->pageTextContains('My Node');
    $this->assertImageRendered('example_1.jpeg');
  }

}
