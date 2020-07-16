<?php

declare(strict_types = 1);

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test video iframe media.
 */
class VideoIframeTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'oe_media_iframe',
  ];

  /**
   * Tests the video iframe uses the attached thumbnail.
   */
  public function testVideoIframeThumbnail() {
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
    $page->attachFileToField('Iframe thumbnail', drupal_get_path('module', 'oe_media') . '/tests/fixtures/example_1.jpeg');
    $this->assertSession()->waitForField('Alternative text');
    $page->fillField('Alternative text', 'thumbnail');
    $page->pressButton('Save');

    $this->assertSession()->pageTextContains('Video iframe EBS has been created.');
    $image_element = $this->assertSession()->elementExists('css', '.priority-low img');
    $this->assertContains('example_1.jpeg', $image_element->getAttribute('src'));
  }

}
