<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\Traits;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;

/**
 * Assertions on the rendered output of the various media types.
 */
trait MediaAssertionsTrait {

  /**
   * Asserts that an AV Portal photo is rendered on the current page.
   *
   * @param string $name
   *   The media name.
   * @param string $src
   *   The expected image source part.
   */
  protected function assertAvPortalPhotoRendered(string $name, string $src): void {
    $this->getMediaByName($name);
    $this->assertSession()->elementExists('css', 'img.avportal-photo[src*="' . $src . '"]');
  }

  /**
   * Asserts that an AV Portal video is rendered on the current page.
   *
   * @param string $name
   *   The media name.
   */
  protected function assertAvPortalVideoRendered(string $name): void {
    $media = $this->getMediaByName($name);
    $this->assertAvPortalVideoReferenceRendered($media->get('oe_media_avportal_video')->value);
  }

  /**
   * Asserts that an AV Portal video reference is rendered on the current page.
   *
   * @param string $reference
   *   The AV Portal video reference.
   */
  protected function assertAvPortalVideoReferenceRendered(string $reference): void {
    $this->assertSession()->elementExists('css', 'iframe[src*="' . $reference . '"]');
  }

  /**
   * Asserts that an image file is rendered on the current page.
   *
   * @param string $filename
   *   The image file name.
   */
  protected function assertImageRendered(string $filename): void {
    $parts = pathinfo($filename);
    $this->assertSession()->elementExists('css', "img[src\$='.{$parts['extension']}'][src*='{$parts['filename']}']");
  }

  /**
   * Asserts that the oEmbed player of a remote video is on the current page.
   *
   * @param string $url
   *   The remote video URL.
   */
  protected function assertOembedIframeRendered(string $url): void {
    $partial_url = Url::fromRoute('media.oembed_iframe', [], [
      'query' => ['url' => $url],
    ])->toString();
    $this->assertSession()->elementExists('css', "iframe[src*='$partial_url']");
  }

  /**
   * Asserts that the Webtools JSON snippet of a media is on the current page.
   *
   * @param string $bundle
   *   The media bundle.
   * @param string $name
   *   The media name.
   */
  protected function assertWebtoolsSnippetRendered(string $bundle, string $name): void {
    $media = $this->getMediaByName($name);
    $this->assertEquals($bundle, $media->bundle());
    // Normalise the JSON the same way the renderer does.
    $snippet = Json::encode(Json::decode($media->get('oe_media_webtools')->value));
    $xpath = "//script[@type='application/json'][.='" . addcslashes($snippet, '\'') . "']";
    $this->assertSession()->elementsCount('xpath', $xpath, 1);
  }

  /**
   * Returns the path of a file in the module test fixtures directory.
   *
   * @param string $filename
   *   The file name.
   *
   * @return string
   *   The path, relative to the Drupal root.
   */
  protected function getFixturePath(string $filename): string {
    return \Drupal::service('extension.list.module')->getPath('oe_media') . '/tests/fixtures/' . $filename;
  }

}
