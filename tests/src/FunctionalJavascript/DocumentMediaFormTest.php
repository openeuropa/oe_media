<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\FunctionalJavascript;

/**
 * Tests the document media creation forms and their referencing.
 *
 * @group oe_media
 * @group batch1
 */
class DocumentMediaFormTest extends MediaFeatureTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->enableMediaStandaloneUrl();
  }

  /**
   * Tests that documents can be uploaded and referenced.
   */
  public function testDocumentUploadAndReference(): void {
    $assert_session = $this->assertSession();

    $editor = $this->drupalCreateUser([
      'administer nodes',
      'access content',
      'create oe_media_demo content',
      'edit own oe_media_demo content',
      'edit any oe_media_demo content',
      'view own unpublished content',
      'create document media',
      'view media',
    ]);
    $this->drupalLogin($editor);

    $this->drupalGet('media/add/document');
    $assert_session->pageTextContains('Add Document');
    $assert_session->pageTextNotContains('One file only.');
    $assert_session->pageTextNotContains('URL');

    $page = $this->getSession()->getPage();
    $page->fillField('Name', 'My Document 1');

    // The file type drives which of the two source fields is shown.
    $page->selectFieldOption('File Type', 'Remote');
    $assert_session->pageTextNotContains('One file only.');
    $assert_session->pageTextContains('URL');

    $page->selectFieldOption('File Type', 'Local');
    $assert_session->pageTextContains('One file only.');
    $assert_session->pageTextNotContains('URL');

    $page->attachFileToField('File', $this->getFixturePath('sample.pdf'));
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save');
    $assert_session->pageTextContains('My Document 1');

    // Reference the document on a node.
    $this->drupalGet('node/add/oe_media_demo');
    $assert_session->pageTextContains('Create OpenEuropa Media Demo');
    $page = $this->getSession()->getPage();
    $page->fillField('Title', 'My Node');
    $page->fillField('field_oe_demo_document_media[0][target_id]', 'My Document 1');
    $page->pressButton('Save');
    $assert_session->pageTextContains('My Node');
    $assert_session->linkExists('sample.pdf');

    // The media is visible to anonymous users while the node is published.
    $media = $this->getMediaByName('My Document 1');
    $this->drupalLogout();
    $this->drupalGet($media->toUrl());
    $assert_session->pageTextContains('My Document 1');

    // Unpublish the node.
    $this->drupalLogin($editor);
    $node = $this->drupalGetNodeByTitle('My Node');
    $this->drupalGet($node->toUrl('edit-form'));
    $this->getSession()->getPage()->uncheckField('Published');
    $this->getSession()->getPage()->pressButton('Save');
    $assert_session->pageTextContains('My Node has been updated');

    // The media follows the access of the node referencing it.
    $this->drupalLogout();
    $this->drupalGet($media->toUrl());
    $assert_session->pageTextContains('Access denied');
  }

  /**
   * Tests the document media widgets available on the demo node form.
   */
  public function testDocumentMediaWidgets(): void {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->drupalCreateUser([
      'access content',
      'create oe_media_demo content',
      'create document media',
      'access media_entity_browser entity browser pages',
      'view media',
    ]));

    $this->drupalGet('node/add/oe_media_demo');

    // The inline entity form exposes the same file type behaviour.
    $assert_session->pageTextNotContains('File type');
    $page = $this->getSession()->getPage();
    $page->pressButton('Add new media item');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('File type');
    $assert_session->pageTextNotContains('One file only.');
    $assert_session->pageTextNotContains('URL');

    $page->selectFieldOption('File Type', 'Remote');
    $assert_session->pageTextNotContains('One file only.');
    $assert_session->pageTextContains('URL');

    $page->selectFieldOption('File Type', 'Local');
    $assert_session->pageTextContains('One file only.');
    $assert_session->pageTextNotContains('URL');

    // Cancel the inline entity form and keep editing the node.
    $page->pressButton('Cancel');
    $assert_session->assertWaitOnAjaxRequest();

    // A document can be created from within the entity browser.
    $page->fillField('Title', 'Media demo');
    $this->openEntityBrowser();
    $this->clickEntityBrowserTab('Add File');

    $browser_page = $this->getSession()->getPage();
    $browser_page->fillField('Name', 'Media document');
    $assert_session->pageTextNotContains('One file only.');
    $assert_session->pageTextNotContains('URL');

    $browser_page->selectFieldOption('File Type', 'Remote');
    $assert_session->pageTextNotContains('One file only.');
    $assert_session->pageTextContains('URL');

    $browser_page->selectFieldOption('File Type', 'Local');
    $assert_session->pageTextContains('One file only.');
    $assert_session->pageTextNotContains('URL');

    $browser_page->attachFileToField('File', $this->getFixturePath('sample.pdf'));
    $assert_session->assertWaitOnAjaxRequest();
    $browser_page->pressButton('Save entity');
    $this->waitForEntityBrowserToClose();
    $this->getSession()->getPage()->pressButton('Save');
    $assert_session->pageTextContains('has been created');
    $assert_session->pageTextNotContains('Error message');
    $assert_session->linkExists('sample.pdf');

    // The same document can then be reused on another node.
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField('Title', 'Media demo');
    $this->openEntityBrowser();
    $this->selectMediaInEntityBrowser('Media document');
    $this->getSession()->getPage()->pressButton('Select entities');
    $this->waitForEntityBrowserToClose();
    $this->getSession()->getPage()->pressButton('Save');
    $assert_session->pageTextContains('has been created');
    $assert_session->linkExists('sample.pdf');
  }

}
