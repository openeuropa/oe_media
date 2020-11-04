<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides tests methods for document media bundle.
 */
class DocumentMediaTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'system',
    'oe_media',
    'oe_media_demo',
    'file',
    'file_link',
    'link',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([], '', TRUE));
  }

  /**
   * Tests the remote file document media.
   */
  public function testRemoteFile(): void {
    $this->drupalGet('media/add/document');

    $assert_session = $this->assertSession();

    // Select the remote option and assert the proper fields are displayed.
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('File Type', 'Remote');
    $assert_session->fieldExists('URL');
    $assert_session->fieldExists('Link text');

    // Fill in the fields to create a document.
    $page->fillField('Name', 'Remote document');
    $page->fillField('URL', 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf');
    $page->pressButton('Save');
    $assert_session->pageTextContains('Document Remote document has been created.');

    // Reference the document in a node.
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField('Title', 'Node with remote file');
    $document_field = $this->getSession()->getPage()->find('css', 'div.field--name-field-oe-demo-document-media');
    $document_field->fillField('Use existing media', 'Remote document');
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->pageTextContains('Node with remote file');
    $this->assertSession()->pageTextContains('https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf');
  }

  /**
   * Tests form validation.
   */
  public function testFormValidation(): void {
    $this->drupalGet('media/add/document');
    $this->getSession()->getPage()->fillField('Name', 'Document');

    // Assert that file type field contains the correct values.
    $this->assertSession()->selectExists('File Type');
    $select_field = $this->getSession()->getPage()->findField('File Type');
    $this->assertEquals([
      '_none' => '- Select a value -',
      'remote' => 'Remote',
      'local' => 'Local',
    ], $this->getOptions($select_field));

    // Assert fields validation.
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertSession()->pageTextContains('File Type field is required.');

    $this->getSession()->getPage()->selectFieldOption('File Type', 'Local');
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertSession()->pageTextContains('File field is required.');

    $this->getSession()->getPage()->selectFieldOption('File Type', 'Remote');
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertSession()->pageTextContains('Remote file URL is required.');
    file_put_contents('test.html', $this->getSession()->getPage()->getOuterHtml());
  }

}
