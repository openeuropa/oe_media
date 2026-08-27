<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\Functional;

/**
 * Tests the creation and the rendering of the iframe media types.
 *
 * @group oe_media
 * @group batch3
 */
class IframeMediaTest extends MediaFeatureTestBase {

  /**
   * The iframe source used across the test.
   */
  protected const IFRAME_URL = 'http://example.com/example.html';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([], '', TRUE));
  }

  /**
   * Tests that iframe media entities can be created and rendered.
   */
  public function testIframeMedia(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('media/add/iframe');
    $assert_session->pageTextContains('Add Iframe');

    // The ratio is optional for this bundle.
    $this->assertEquals([
      '_none' => '- None -',
      '16_9' => '16:9',
      '4_3' => '4:3',
      '3_2' => '3:2',
      '1_1' => '1:1',
    ], $this->getSelectOptions($assert_session->selectExists('Ratio')));

    $page->fillField('Name', 'My Iframe media');
    $page->fillField('Iframe title', 'My custom iframe title');
    $page->fillField('Iframe', '<iframe src="' . self::IFRAME_URL . '" title="Iframe title" width="800" height="600" frameborder="0"><a href="#">Some text.</a></iframe><script type="text/javascript">alert(\'no js\')</script><p>Unwanted text.</p>More unwanted text.<iframe src="' . self::IFRAME_URL . '" allowfullscreen="true"></iframe>');

    $assert_session->pageTextContains('Allowed HTML tags: <iframe allowfullscreen height importance loading referrerpolicy sandbox src width mozallowfullscreen webkitAllowFullScreen scrolling frameborder title>');
    $assert_session->pageTextContains('Only one iframe tag allowed. All other content will be stripped.');
    $assert_session->pageTextContains('If no ratio is chosen, the width and height specified in the iframe will be used.');

    $page->pressButton('Save');
    $assert_session->pageTextContains('Iframe My Iframe media has been created.');

    $this->drupalGet('node/add/oe_media_demo');
    $assert_session->pageTextContains('Create OpenEuropa Media Demo');
    $page->fillField('Title', 'My Node with iframe');
    $page->fillField('field_oe_demo_iframe[0][target_id]', 'My Iframe media');
    $page->pressButton('Save');
    $assert_session->pageTextContains('My Node with iframe');

    // The iframe has been embedded with everything but the first iframe tag
    // stripped.
    $assert_session->responseContains('<iframe src="' . self::IFRAME_URL . '" title="My custom iframe title" width="800" height="600" frameborder="0">Some text.</iframe>');
    $assert_session->responseNotContains('Unwanted text.');
    $assert_session->responseNotContains('More unwanted text.');
    $assert_session->responseNotContains('<iframe src="' . self::IFRAME_URL . '" allowfullscreen="true"></iframe>');
  }

  /**
   * Tests that video iframe media entities can be created and rendered.
   */
  public function testVideoIframeMedia(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('media/add/video_iframe');
    $assert_session->pageTextContains('Add Video iframe');

    // The ratio is required for this bundle, so no empty option is offered.
    $this->assertEquals([
      '16_9' => '16:9',
      '4_3' => '4:3',
      '3_2' => '3:2',
      '1_1' => '1:1',
    ], $this->getSelectOptions($assert_session->selectExists('Ratio')));

    $page->fillField('Name', 'EBS');
    $page->fillField('Iframe title', 'My custom iframe title');
    $page->fillField('Iframe', '<iframe src="' . self::IFRAME_URL . '" width="800" height="600" frameborder="0"><a href="#">Some text.</a></iframe><script type="text/javascript">alert(\'no js\')</script><p>Unwanted text.</p>More unwanted text.<iframe src="' . self::IFRAME_URL . '" allowfullscreen="true"></iframe>');

    $assert_session->pageTextContains('Allowed HTML tags: <iframe allowfullscreen height importance loading referrerpolicy sandbox src width mozallowfullscreen webkitAllowFullScreen scrolling frameborder title>');
    $assert_session->pageTextContains('Only one iframe tag allowed. All other content will be stripped.');

    $page->pressButton('Save');
    $assert_session->pageTextContains('Video iframe EBS has been created.');

    $this->drupalGet('node/add/oe_media_demo');
    $assert_session->pageTextContains('Create OpenEuropa Media Demo');
    $page->fillField('Title', 'My Node');
    $page->fillField('field_oe_demo_video_iframe[0][target_id]', 'EBS');
    $page->pressButton('Save');
    $assert_session->pageTextContains('My Node');

    // The source iframe has no title attribute, so the media one is prepended.
    $assert_session->responseContains('<iframe title="My custom iframe title" src="' . self::IFRAME_URL . '" width="800" height="600" frameborder="0">Some text.</iframe>');
    $assert_session->responseNotContains('Unwanted text.');
    $assert_session->responseNotContains('More unwanted text.');
    $assert_session->responseNotContains('<iframe src="' . self::IFRAME_URL . '" allowfullscreen="true"></iframe>');
  }

}
