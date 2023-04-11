<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_iframe\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test video iframe media.
 */
class VideoIframeTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_media_iframe',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the video iframe uses the attached thumbnail.
   */
  public function testVideoIframeThumbnail(): void {
    $user = $this->createUser([], '', TRUE);
    $this->drupalLogin($user);

    $this->drupalGet('/media/add/video_iframe');
    $this->assertSession()->fieldExists('Name');
    $this->assertSession()->fieldExists('Iframe');
    $this->assertSession()->fieldExists('Iframe thumbnail');
    $this->assertSession()->fieldExists('Ratio');

    $page = $this->getSession()->getPage();
    $page->fillField('Name', 'EBS');
    $page->fillField('Iframe', '<iframe src=\"http://web:8080/tests/fixtures/example.html\" width=\"800\" height=\"600\" frameborder=\"0\"><a href=\"#\">invalid</a></iframe><script type=\"text/javascript\">alert(\'no js\')</script>');
    // Upload an image as thumbnail.
    $page->attachFileToField('Iframe thumbnail', \Drupal::service('extension.list.module')->getPath('oe_media') . '/tests/fixtures/example_1.jpeg');
    $this->assertSession()->waitForField('Alternative text');
    $page->fillField('Alternative text', 'thumbnail');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('Video iframe EBS has been created.');
    $image_element = $this->assertSession()->elementExists('css', '.layout-content table tbody tr:nth-child(1) img');
    // Check that the uploaded image is used as thumbnail for the media.
    $this->assertStringContainsString('example_1.jpeg', $image_element->getAttribute('src'));

    // Edit the video iframe to remove thumbnail.
    $this->getSession()->getPage()->clickLink('Edit');
    $page = $this->getSession()->getPage();
    $page->pressButton('Remove');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('Video iframe EBS has been updated.');
    $image_element = $this->assertSession()->elementExists('css', '.layout-content table tbody tr:nth-child(1) img');
    // Default thumbnail should replace the removed thumbnail.
    $this->assertStringContainsString('video.png', $image_element->getAttribute('src'));
  }

}
