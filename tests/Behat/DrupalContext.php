<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\node\NodeInterface;

/**
 * The main Drupal context.
 */
class DrupalContext extends RawDrupalContext {

  /**
   * The config context.
   *
   * @var \Drupal\DrupalExtension\Context\ConfigContext
   */
  protected $configContext;

  /**
   * Gathers some other contexts.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The before scenario scope.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();
    $this->configContext = $environment->getContext('Drupal\DrupalExtension\Context\ConfigContext');
  }

  /**
   * Clicks on a fieldset form element.
   *
   * @param string $field
   *   The name of the fieldset.
   *
   * @Given I click the fieldset :field
   */
  public function assertClickFieldset(string $field): void {
    $this->getSession()->getPage()->find('named', ['link_or_button', $field])->click();
  }

  /**
   * Switches to the iframe of the Demo entity browser.
   *
   * @Then I should see entity browser modal window
   */
  public function iSwitchToIframe(): void {
    $this->getSession()->switchToIFrame('entity_browser_iframe_media_entity_browser');
  }

  /**
   * Selects required media entity from entity browser.
   *
   * @param string $name
   *   The name of the media.
   *
   * @When I select the :media_name media (entity )in the entity browser modal window
   */
  public function selectMediaInEntityBrowser(string $name): void {
    $xpath = '//div[@class and contains(concat(" ", normalize-space(@class), " "), " views-row ")]';
    $xpath .= '[.//div[@class and contains(concat(" ", normalize-space(@class), " "), " views-field-name ")]';
    $xpath .= '/div[@class and contains(concat(" ", normalize-space(@class), " "), " media-info ")]';
    // The last text node contains the media name.
    $xpath .= '[normalize-space(text()[last()]) = "' . $name . '"]]';
    $xpath .= '//input[@type="checkbox"]';
    $this->assertSession()->elementExists('xpath', $xpath)->check();
  }

  /**
   * Step that deletes a media entity.
   *
   * @param string $title
   *   The media title.
   *
   * @Then I remove the media :title
   */
  public function removeMediaEntity(string $title): void {
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['name' => $title]);
    if (!$media) {
      throw new \Exception(sprintf('The media named "%s" does not exist', $title));
    }

    $media = reset($media);
    $media->delete();
  }

  /**
   * Fills the correct media reference field with a value.
   *
   * @param string $media_type
   *   Which media reference field to reference.
   * @param string $value
   *   The value to enter in the field.
   *
   * @When I fill in the :media_type reference field with :value
   */
  public function fillMediaReferenceField(string $media_type, string $value): void {
    $mappings = [
      'iframe' => 'field_oe_demo_iframe',
      'image' => 'field_oe_demo_image_media',
      'document' => 'field_oe_demo_document_media',
      'remote video' => 'field_oe_demo_remote_video_media',
      'video iframe' => 'field_oe_demo_video_iframe',
    ];

    if (!isset($mappings[$media_type])) {
      throw new \Exception(sprintf('Invalid media type "%s" specified.', $media_type));
    }

    $this->getSession()->getPage()
      ->fillField($mappings[$media_type] . '[0][target_id]', $value);
  }

  /**
   * Checks that a given image is present in the page.
   *
   * @param string $filename
   *   The image filename.
   *
   * @Then I (should )see the image :filename
   */
  public function assertImagePresent(string $filename): void {
    // Drupal appends an underscore and a number to the filename when duplicate
    // files are uploaded, for example when a test runs more then once.
    // We split up the filename and extension and match for both.
    $parts = pathinfo($filename);
    $extension = $parts['extension'];
    $filename = $parts['filename'];
    $this->assertSession()->elementExists('css', "img[src$='.$extension'][src*='$filename']");
  }

  /**
   * Checks that an OEmbed is present in the page for a certain url.
   *
   * @param string $url
   *   The video url.
   *
   * @Then I (should )see the embedded video player for :url
   */
  public function assertOembedIframePresent(string $url): void {
    $partial_iframe_url = Url::fromRoute('media.oembed_iframe', [], [
      'query' => [
        'url' => $url,
      ],
    ])->toString();
    $this->assertSession()->elementExists('css', "iframe[src*='$partial_iframe_url']");
  }

  /**
   * Navigates to the canonical page of a node.
   *
   * @param string $title
   *   The title of the node.
   *
   * @When (I )go to the :title node page
   * @When (I )visit the :title node page
   */
  public function visitNodePage(string $title): void {
    $node = $this->getNodeByTitle($title);
    $this->visitPath($node->toUrl()->toString());
  }

  /**
   * Retrieves a node by its title.
   *
   * @param string $title
   *   The node title.
   *
   * @return \Drupal\node\NodeInterface
   *   The node entity.
   */
  protected function getNodeByTitle(string $title): NodeInterface {
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $storage->loadByProperties([
      'title' => $title,
    ]);

    if (!$nodes) {
      throw new \Exception("Could not find node with title '$title'.");
    }

    if (count($nodes) > 1) {
      throw new \Exception("Multiple nodes with title '$title' found.");
    }

    return reset($nodes);
  }

}
