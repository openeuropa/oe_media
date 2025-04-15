<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media_circabc\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\oe_media\Traits\MediaTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\views\Entity\View;

/**
 * Provides tests methods for document media bundle.
 */
class DocumentMediaTest extends WebDriverTestBase {

  use MediaTestTrait;

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
    'oe_media_circabc',
    'oe_media_circabc_mock',
    'file',
    'file_link',
    'link',
    'options',
    'content_translation',
    'language',
    'views',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([], '', TRUE));

    $this->writeSettings([
      'settings' => [
        'circabc' => [
          'url' => (object) [
            'value' => 'https://example.com/circabc-ewpp',
            'required' => TRUE,
          ],
          'category' => (object) [
            'value' => '1111',
            'required' => TRUE,
          ],
          'username' => (object) [
            'value' => 'username',
            'required' => TRUE,
          ],
          'password' => (object) [
            'value' => 'password',
            'required' => TRUE,
          ],
        ],
      ],
    ]);

    // Set the reference field onto the display.
    $form_display = EntityFormDisplay::load('media.document.default');
    $form_display->setComponent('oe_media_circabc_reference', [
      'type' => 'oe_media_circabc_default_widget',
    ]);
    $form_display->save();
    $view_display = EntityViewDisplay::load('media.document.default');
    $view_display->setComponent('oe_media_circabc_reference', [
      'type' => 'oe_media_circabc_default',
    ]);
    $view_display->save();

    $view_display = EntityViewDisplay::load('node.oe_media_demo.default');
    $view_display->setComponent('field_ief_document_media', [
      'type' => 'entity_reference_entity_view',
    ]);
    $view_display->save();

    ConfigurableLanguage::createFromLangcode('fr')->save();
    \Drupal::service('content_translation.manager')->setEnabled('media', 'document', TRUE);

    $field = FieldConfig::load('media.document.oe_media_circabc_reference');
    $field->setTranslatable(TRUE);
    $field->save();

  }

  /**
   * Tests the CircaBC document media.
   */
  public function testCircaBcDocumentCreation(): void {
    $this->drupalGet('media/add/document');
    $assert_session = $this->assertSession();

    $page = $this->getSession()->getPage();
    $page->selectFieldOption('File Type', 'CircaBC');
    $this->assertFalse($page->findField('URL')->isVisible());
    $this->assertFalse($page->findField('Link text')->isVisible());
    $this->assertFalse($page->findField('Name')->isVisible());
    $this->assertFalse($page->findField('Name')->hasAttribute('required'));
    $this->assertTrue($page->findField('The CircaBC URL')->isVisible());
    $page->selectFieldOption('File Type', 'Remote');
    $this->assertTrue($page->findField('URL')->isVisible());
    $this->assertTrue($page->findField('Link text')->isVisible());
    $this->assertTrue($page->findField('Name')->isVisible());
    $this->assertTrue($page->findField('Name')->hasAttribute('required'));
    $this->assertFalse($page->findField('The CircaBC URL')->isVisible());
    $page->selectFieldOption('File Type', 'CircaBC');
    $this->assertFalse($page->findField('URL')->isVisible());
    $this->assertFalse($page->findField('Link text')->isVisible());
    $this->assertTrue($page->findField('The CircaBC URL')->isVisible());
    $this->assertFalse($page->findField('Name')->isVisible());
    $this->assertFalse($page->findField('Name')->hasAttribute('required'));

    // Fill in the fields to create a document.
    $page->fillField('CircaBC URL', 'https://example.com/circabc-ewpp/ui/group/85a095a8-aacb-4ae2-9f67-c90a789e353e/library/e74e3bc0-a639-4e04-a839-3bbd60ed5688/details');
    $page->pressButton('Save');

    // The media name was taken from the remote document.
    $assert_session->pageTextContains('Document Test sample file has been created.');

    // Reference the document in a node and assert we render a default link.
    $this->drupalGet('node/add/oe_media_demo');
    $this->getSession()->getPage()->fillField('Title', 'Node with remote file');
    $document_field = $this->getSession()->getPage()->find('css', 'div.field--name-field-oe-demo-document-media');
    $document_field->fillField('Use existing media', 'Test sample file');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('Node with remote file');
    $link = $page->findLink('sample_pdf.pdf');
    $this->assertEquals('https://example.com/circabc-ewpp/d/d/workspace/SpacesStore/e74e3bc0-a639-4e04-a839-3bbd60ed5688/download', $link->getAttribute('href'));

    // Load the media and assert the data.
    $media = $this->getMediaByName('Test sample file');
    $reference = $media->get('oe_media_circabc_reference')->first()->getValue();
    $this->assertEquals('e74e3bc0-a639-4e04-a839-3bbd60ed5688', $reference['uuid']);
    $this->assertEquals('3028', $reference['size']);
    $this->assertEquals('application/pdf', $reference['mime']);
    $this->assertEquals('sample_pdf.pdf', $reference['filename']);
    $french = $media->getTranslation('fr');
    $reference = $french->get('oe_media_circabc_reference')->first()->getValue();
    $this->assertEquals('5d634abd-fec1-452a-ae0b-62e4cf080506', $reference['uuid']);
    $this->assertEquals('3028', $reference['size']);
    $this->assertEquals('application/pdf', $reference['mime']);
    $this->assertEquals('sample_pdf_FR.pdf', $reference['filename']);

    // Edit the media and assert the form elements.
    $this->drupalGet($media->toUrl('edit-form'));
    // The name, file type and reference fields are disabled.
    $disabled = ['name[0][value]', 'oe_media_file_type', 'oe_media_circabc_reference[0][uuid]'];
    foreach ($disabled as $name) {
      $this->assertTrue($page->findField($name)->hasAttribute('disabled'));
    }
    // We also cannot see the other document type fields.
    $this->assertFalse($page->findField('URL')->isVisible());
    $this->assertFalse($page->findField('Link text')->isVisible());

    // Edit the translation and assert the disabled fields.
    $this->drupalGet('/fr/media/' . $media->id() . '/edit', ['external' => FALSE]);
    $this->assertTrue($page->findField('name[0][value]')->hasAttribute('disabled'));
    // We cannot change the file type nor any of the other fields.
    $assert_session->fieldNotExists('File Type');
    $assert_session->fieldNotExists('URL');
    $assert_session->fieldNotExists('Link text');
    $assert_session->fieldNotExists('The CircaBC URL');

    // Edit the media and save again. Assert that values have no changed.
    $this->drupalGet($media->toUrl('edit-form'));
    $page->pressButton('Save');
    $assert_session->pageTextContains('Document Test sample file has been updated.');
    $media = $this->getMediaByName('Test sample file');
    $reference = $media->get('oe_media_circabc_reference')->first()->getValue();
    $this->assertEquals('e74e3bc0-a639-4e04-a839-3bbd60ed5688', $reference['uuid']);
    $this->assertEquals('3028', $reference['size']);
    $this->assertEquals('application/pdf', $reference['mime']);
    $this->assertEquals('sample_pdf.pdf', $reference['filename']);
    $french = $media->getTranslation('fr');
    $reference = $french->get('oe_media_circabc_reference')->first()->getValue();
    $this->assertEquals('5d634abd-fec1-452a-ae0b-62e4cf080506', $reference['uuid']);
    $this->assertEquals('3028', $reference['size']);
    $this->assertEquals('application/pdf', $reference['mime']);
    $this->assertEquals('sample_pdf_FR.pdf', $reference['filename']);
  }

  /**
   * Tests the CircaBC document media in IEF.
   */
  public function testIefCircaBcDocumentCreation(): void {
    $this->drupalGet('node/add/oe_media_demo');
    $page = $this->getSession()->getPage();
    $page->fillField('Title', 'Test node');
    $page->pressButton('Add new media item');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldExists('Name');
    $this->assertSession()->fieldExists('File Type');
    $this->assertFalse($page->findField('URL')->isVisible());
    $this->assertFalse($page->findField('Link text')->isVisible());
    $this->assertFalse($page->findField('The CircaBC URL')->isVisible());

    $page->selectFieldOption('File Type', 'Remote');
    $this->assertTrue($page->findField('URL')->isVisible());
    $this->assertTrue($page->findField('Link text')->isVisible());
    $this->assertTrue($page->findField('Name')->isVisible());
    $this->assertFalse($page->findField('The CircaBC URL')->isVisible());
    $page->selectFieldOption('File Type', 'CircaBC');
    $this->assertFalse($page->findField('URL')->isVisible());
    $this->assertFalse($page->findField('Link text')->isVisible());
    $this->assertFalse($page->findField('Name')->isVisible());
    $this->assertTrue($page->findField('The CircaBC URL')->isVisible());

    // Fill in the fields to create a document.
    $page->fillField('CircaBC URL', 'https://example.com/circabc-ewpp/ui/group/85a095a8-aacb-4ae2-9f67-c90a789e353e/library/e74e3bc0-a639-4e04-a839-3bbd60ed5688/details');
    $page->pressButton('Create media item');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementContains('css', 'tbody td.inline-entity-form-media-label', 'CircaBC Document');
    $this->assertSession()->fieldNotExists('File Type');

    // Press to edit and assert the URL is still there (the media did not get
    // created yet).
    $page->pressButton('ief-field_ief_document_media-form-entity-edit-0');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertFalse($page->findField('URL')->isVisible());
    $this->assertFalse($page->findField('Link text')->isVisible());
    $this->assertTrue($page->findField('The CircaBC URL')->isVisible());
    $this->assertSession()->fieldValueEquals('The CircaBC URL', 'https://example.com/circabc-ewpp/ui/group/85a095a8-aacb-4ae2-9f67-c90a789e353e/library/e74e3bc0-a639-4e04-a839-3bbd60ed5688/details');

    // Save again and save the node.
    $page->pressButton('Update media item');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementContains('css', 'tbody td.inline-entity-form-media-label', 'CircaBC Document');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('OpenEuropa Media Demo Test node has been created.');
    $link = $page->findLink('sample_pdf.pdf');
    $this->assertEquals('https://example.com/circabc-ewpp/d/d/workspace/SpacesStore/e74e3bc0-a639-4e04-a839-3bbd60ed5688/download', $link->getAttribute('href'));
    $this->assertSession()->pageTextContains('application/pdf, 2.96 KB');

    // Edit the node and assert the edit for inside IEF of the document.
    $node = $this->drupalGetNodeByTitle('Test node');
    $this->drupalGet($node->toUrl('edit-form'));
    $page->pressButton('ief-field_ief_document_media-form-entity-edit-0');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // The media name was updated based on CircaBC.
    $this->assertSession()->fieldValueEquals('Name', 'Test sample file');
    $this->assertSession()->fieldValueEquals('CircaBC Reference', 'e74e3bc0-a639-4e04-a839-3bbd60ed5688');
    // The name, file type and reference fields are disabled.
    $disabled = [
      'field_ief_document_media[form][inline_entity_form][entities][0][form][name][0][value]',
      'field_ief_document_media[form][inline_entity_form][entities][0][form][oe_media_file_type]',
      'field_ief_document_media[form][inline_entity_form][entities][0][form][oe_media_circabc_reference][0][uuid]',
    ];
    foreach ($disabled as $name) {
      $this->assertTrue($page->findField($name)->hasAttribute('disabled'));
    }
    // We also cannot see the other document type fields.
    $this->assertSession()->fieldNotExists('The CircaBC URL');
    $this->assertFalse($page->findField('URL')->isVisible());
    $this->assertFalse($page->findField('Link text')->isVisible());

    $page->pressButton('Update media item');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementContains('css', 'tbody td.inline-entity-form-media-label', 'Test sample file');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('OpenEuropa Media Demo Test node has been updated.');
    $link = $page->findLink('sample_pdf.pdf');
    $this->assertEquals('https://example.com/circabc-ewpp/d/d/workspace/SpacesStore/e74e3bc0-a639-4e04-a839-3bbd60ed5688/download', $link->getAttribute('href'));
    $this->assertSession()->pageTextContains('application/pdf, 2.96 KB');

    // Load the media and assert the values.
    $media = $this->getMediaByName('Test sample file');
    $reference = $media->get('oe_media_circabc_reference')->first()->getValue();
    $this->assertEquals('e74e3bc0-a639-4e04-a839-3bbd60ed5688', $reference['uuid']);
    $this->assertEquals('3028', $reference['size']);
    $this->assertEquals('application/pdf', $reference['mime']);
    $this->assertEquals('sample_pdf.pdf', $reference['filename']);
    $french = $media->getTranslation('fr');
    $reference = $french->get('oe_media_circabc_reference')->first()->getValue();
    $this->assertEquals('5d634abd-fec1-452a-ae0b-62e4cf080506', $reference['uuid']);
    $this->assertEquals('3028', $reference['size']);
    $this->assertEquals('application/pdf', $reference['mime']);
    $this->assertEquals('sample_pdf_FR.pdf', $reference['filename']);
  }

  /**
   * Tests CircaBC Entity Browser widget that is based on Views.
   */
  public function testCircaBcEntityBrowserWidget(): void {
    // Visit the iframe of the Entity Browser.
    $this->drupalGet('/entity-browser/modal/circabc');
    $this->assertCount(2, $this->getSession()->getPage()->findAll('css', '.views-col'));

    // Assert the exposed filters.
    $this->assertSession()->fieldExists('Search');
    $this->assertSession()->fieldExists('Interest group');
    $this->assertSession()->fieldExists('Language');

    $this->getSession()->getPage()->selectFieldOption('Language', 'All');
    $this->getSession()->getPage()->pressButton('Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertCount(4, $this->getSession()->getPage()->findAll('css', '.views-col'));

    // We have 0 pager items.
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertCount(0, $elements);

    // Edit the view and set a pager of 2.
    $view = View::load('circabc_entity_browser');
    $view->getDisplay('default')['display_options']['pager']['options']['items_per_page'] = 2;
    $view->save();
    $this->drupalGet('/entity-browser/modal/circabc');
    $this->getSession()->getPage()->selectFieldOption('Language', 'All');
    $this->getSession()->getPage()->pressButton('Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertCount(4, $elements);

    $entity_type_manager = $this->container->get('entity_type.manager');
    $media_title = 'Test sample file';

    // Make a selection and make sure the entity gets created.
    $this->assertEmpty($entity_type_manager->getStorage('media')->loadMultiple());
    $this->getSession()->getPage()->checkField('entity_browser_select[e74e3bc0-a639-4e04-a839-3bbd60ed5688]');
    $this->getSession()->getPage()->pressButton('Select entities');
    $this->assertSingleMediaEntity($media_title);

    // Make the same selection again and make sure the entity gets reused.
    $this->drupalGet('/entity-browser/modal/circabc');
    $this->getSession()->getPage()->checkField('entity_browser_select[e74e3bc0-a639-4e04-a839-3bbd60ed5688]');
    $this->getSession()->getPage()->pressButton('Select entities');
    $this->assertSingleMediaEntity($media_title);
  }

  /**
   * Tests that we can switch from local or remote to CircaBC.
   */
  public function testSwitchToCircaBc(): void {
    $this->container->get('file_system')->copy(
      \Drupal::service('extension.list.module')
        ->getPath('oe_media') . '/tests/fixtures/sample.pdf',
      'public://sample.pdf'
    );

    $this->container->get('file_system')->copy(
      \Drupal::service('extension.list.module')
        ->getPath('oe_media') . '/tests/fixtures/sample.pdf',
      'public://sample_2.pdf'
    );

    $file_one = File::create([
      'uri' => 'public://sample.pdf',
    ]);
    $file_one->save();

    $file_two = File::create([
      'uri' => 'public://sample_2.pdf',
    ]);
    $file_two->save();

    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');

    /** @var \Drupal\media\MediaInterface $media */
    $local_media = $media_storage->create([
      'name' => 'a document media',
      'bundle' => 'document',
      'oe_media_file_type' => 'local',
      'oe_media_file' => $file_one,
    ]);
    $local_media->addTranslation('fr', ['oe_media_file' => $file_two] + $local_media->toArray());
    $local_media->save();

    $this->drupalGet($local_media->toUrl('edit-form'));
    $this->getSession()->getPage()->selectFieldOption('File Type', 'CircaBC');
    $this->getSession()->getPage()->fillField('CircaBC URL', 'https://example.com/circabc-ewpp/ui/group/85a095a8-aacb-4ae2-9f67-c90a789e353e/library/e74e3bc0-a639-4e04-a839-3bbd60ed5688/details');
    $this->getSession()->getPage()->pressButton('Save');
    $media_storage->resetCache();
    $local_media = $media_storage->load($local_media->id());
    $this->assertEquals('circabc', $local_media->get('oe_media_file_type')->value);
    $this->assertTrue($local_media->get('oe_media_file')->isEmpty());
    $this->assertTrue($local_media->getTranslation('fr')->get('oe_media_file')->isEmpty());

    /** @var \Drupal\media\MediaInterface $media */
    $remote_media = $media_storage->create([
      'name' => 'a document media',
      'bundle' => 'document',
      'oe_media_file_type' => 'remote',
      'oe_media_remote_file' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
    ]);
    $remote_media->addTranslation('fr', ['oe_media_remote_file' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy-fr.pdf'] + $remote_media->toArray());
    $remote_media->save();

    $this->drupalGet($remote_media->toUrl('edit-form'));
    $this->getSession()->getPage()->selectFieldOption('File Type', 'CircaBC');
    $this->getSession()->getPage()->fillField('CircaBC URL', 'https://example.com/circabc-ewpp/ui/group/85a095a8-aacb-4ae2-9f67-c90a789e353e/library/e74e3bc0-a639-4e04-a839-3bbd60ed5688/details');
    $this->getSession()->getPage()->pressButton('Save');
    $media_storage->resetCache();
    $remote_media = $media_storage->load($remote_media->id());
    $this->assertEquals('circabc', $remote_media->get('oe_media_file_type')->value);
    $this->assertTrue($remote_media->get('oe_media_remote_file')->isEmpty());
    $this->assertTrue($remote_media->getTranslation('fr')->get('oe_media_remote_file')->isEmpty());
  }

  /**
   * Asserts that only a single Media entity with the given title was created.
   *
   * @param string $title
   *   The media title.
   */
  protected function assertSingleMediaEntity(string $title): void {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entities = $entity_type_manager->getStorage('media')->loadMultiple();
    $this->assertCount(1, $entities);
    $media = reset($entities);
    $this->assertEquals($title, trim($media->label()));
  }

}
