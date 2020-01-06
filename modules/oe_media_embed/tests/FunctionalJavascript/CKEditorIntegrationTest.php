<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media_embed\FunctionalJavascript;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests CKEditor integration.
 */
class CKEditorIntegrationTest extends MediaEmbedTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
  ];

  /**
   * The test button.
   *
   * @var \Drupal\embed\Entity\EmbedButton
   */
  protected $button;

  /**
   * The test administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->button = $this->container->get('entity_type.manager')
      ->getStorage('embed_button')
      ->load('media');

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'access embed_media_entity_browser_test entity browser pages',
      'administer filters',
      'administer display modes',
      'administer embed buttons',
      'administer site configuration',
      'administer display modes',
      'administer content types',
      'administer node display',
      'access content',
      'create page content',
      'edit own page content',
      'use text format html',
    ]);

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests integration with CKEditor.
   *
   * We test that the we can configure a text format to use our widget and
   * that we can embed Media entities in the WYSIWYG.
   */
  public function testIntegration(): void {
    $this->addEmbedButtonToToolbar();
    // Verify that the Entity Embed button shows up and results in an
    // operational entity embedding experience in the text editor.
    $this->drupalGet('/node/add/page');
    $this->assignNameToCkeditorIframe();

    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertSession()->pageTextNotContains('My image media');
    $this->assertSession()->pageTextNotContains('Digital Single Market: cheaper calls to other EU countries as of 15 May');

    // Embed the Image media.
    $this->getSession()->switchToIFrame();
    $this->assertSession()->elementExists('css', 'a.cke_button__' . $this->button->id())->click();
    $this->assertSession()->waitForId('drupal-modal');
    $this->assertSession()->fieldExists('entity_id')->setValue('My image media (1)');
    $this->assertSession()->elementExists('css', 'button.js-button-next')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->responseContains('Selected entity');
    $this->assertSession()->linkExists('My image media');
    $this->assertSession()->fieldExists('Display as')->selectOption('Image teaser');
    $this->assertSession()->elementExists('css', 'button.button--primary')->press();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Embed the Remote video media.
    $this->assertSession()->elementExists('css', 'a.cke_button__' . $this->button->id())->click();
    $this->assertSession()->waitForId('drupal-modal');
    $this->assertSession()->fieldExists('entity_id')->setValue('Digital Single Market: cheaper calls to other EU countries as of 15 May (2)');
    $this->assertSession()->elementExists('css', 'button.js-button-next')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->responseContains('Selected entity');
    $this->assertSession()->linkExists('Digital Single Market: cheaper calls to other EU countries as of 15 May');
    $this->assertSession()->fieldNotExists('Display as');
    $this->assertSession()->elementExists('css', 'button.button--primary')->press();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that the embedded entity gets a preview inside the text editor.
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertSession()->pageTextContains('My image media');
    $this->assertSession()->pageTextContains('Digital Single Market: cheaper calls to other EU countries as of 15 May');
    $this->getSession()->switchToIFrame();
    $this->getSession()->getPage()->fillField('Title', 'Node with embedded media');
    $this->assertSession()->buttonExists('Save')->press();

    // Verify that the embedded media are found in the markup.
    $media = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->loadMultiple();

    $element = new FormattableMarkup('<p data-oembed="https://oembed.ec.europa.eu?url=https%3A//data.ec.europa.eu/ewp/media/@uuid%3Fview_mode%3Dimage_teaser"><a href="https://data.ec.europa.eu/ewp/media/@uuid">@title</a></p>', ['@uuid' => $media[1]->uuid(), '@title' => $media[1]->label()]);
    $this->assertContains($element->__toString(), $this->getSession()->getPage()->getHtml());

    $element = new FormattableMarkup('<p data-oembed="https://oembed.ec.europa.eu?url=https%3A//data.ec.europa.eu/ewp/media/@uuid%3Fview_mode%3Doe_embed"><a href="https://data.ec.europa.eu/ewp/media/@uuid">@title</a></p>', ['@uuid' => $media[2]->uuid(), '@title' => $media[2]->label()]);
    $this->assertContains($element->__toString(), $this->getSession()->getPage()->getHtml());
  }

  /**
   * Tests the integration with entity browsers.
   */
  public function testEntityBrowserIntegration(): void {
    $this->addEmbedButtonToToolbar();

    // Enable the entity browser integration.
    $this->drupalGet('admin/config/content/embed/button/manage/media');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('Entity browser', 'embed_media_entity_browser_test');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('The embed button Media has been updated.');

    $this->drupalGet('/node/add/page');
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertSession()->pageTextNotContains('My image media');
    $this->assertSession()->pageTextNotContains('Digital Single Market: cheaper calls to other EU countries as of 15 May');

    // Embed the Image media.
    $this->getSession()->switchToIFrame();
    $this->assertSession()->elementExists('css', 'a.cke_button__' . $this->button->id())->click();
    $this->assertSession()->waitForId('drupal-modal');
    $this->getSession()->switchToIFrame('entity_browser_iframe_embed_media_entity_browser_test');
    // Check the image checkbox.
    $this->getSession()->getPage()->checkField('entity_browser_select[media:1]');
    $this->assertSession()->buttonExists('Select entities')->click();
    $this->getSession()->switchToIFrame();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Selected entity');
    $this->assertSession()->linkExists('My image media');
    $this->assertSession()->fieldExists('Display as')->selectOption('Image teaser');
    // Press the "Embed" button in the modal actions.
    $this->assertSession()->elementExists('css', 'button.button--primary')->press();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Embed the Remote video media.
    $this->getSession()->switchToIFrame();
    $this->assertSession()->elementExists('css', 'a.cke_button__' . $this->button->id())->click();
    $this->assertSession()->waitForId('drupal-modal');
    $this->getSession()->switchToIFrame('entity_browser_iframe_embed_media_entity_browser_test');
    // Check the video checkbox.
    $this->getSession()->getPage()->checkField('entity_browser_select[media:2]');
    $this->assertSession()->buttonExists('Select entities')->click();
    $this->getSession()->switchToIFrame();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Selected entity');
    $this->assertSession()->linkExists('Digital Single Market: cheaper calls to other EU countries as of 15 May');
    // Press the "Embed" button in the modal actions.
    $this->assertSession()->elementExists('css', 'button.button--primary')->press();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that the embedded entities get a preview inside the text editor.
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertSession()->pageTextContains('My image media');
    $this->assertSession()->pageTextContains('Digital Single Market: cheaper calls to other EU countries as of 15 May');
    $this->getSession()->switchToIFrame();
  }

  /**
   * Adds the "Embed" button to the "Html" text format toolbar.
   */
  protected function addEmbedButtonToToolbar(): void {
    // Add "Embed" toolbar button group to the active toolbar.
    $this->drupalGet('admin/config/content/formats/manage/html');
    $this->assertSession()->buttonExists('Show group names')->press();
    $this->assertSession()->waitForElementVisible('css', '.ckeditor-add-new-group');
    $this->assertSession()->buttonExists('Add group')->press();
    $this->assertSession()->waitForElementVisible('css', '[name="group-name"]')->setValue('Embeds');
    $this->assertSession()->buttonExists('Apply')->press();

    // Drag the Media embed button to the toolbar.
    $target = $this->assertSession()->waitForElementVisible('css', 'ul.ckeditor-toolbar-group-buttons');
    $buttonElement = $this->assertSession()->elementExists('xpath', '//li[@data-drupal-ckeditor-button-name="' . $this->button->id() . '"]');
    $buttonElement->dragTo($target);

    $this->assertSession()->buttonExists('Save configuration')->press();
    $this->assertSession()->responseContains('The text format <em class="placeholder">Html format</em> has been updated.');
  }

  /**
   * Assigns a name to the CKEditor iframe, to allow use of ::switchToIFrame().
   *
   * @see \Behat\Mink\Session::switchToIFrame()
   */
  protected function assignNameToCkeditorIframe() {
    $javascript = <<<JS
(function(){
  document.getElementsByClassName('cke_wysiwyg_frame')[0].id = 'ckeditor';
})()
JS;
    $this->getSession()->evaluateScript($javascript);
  }

}
