<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media_circabc\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\oe_media_circabc\CircaBc\CircaBcClient;
use Drupal\Tests\oe_media\Kernel\MediaTestBase;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\oe_media_circabc\Plugin\views\query\CircaBcQuery;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Tests the document media type.
 */
class DocumentMediaTest extends MediaTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Enable also these modules to assert that the tests in this class work
    // also with this module enabled.
    'oe_media_circabc',
    'oe_media_circabc_mock',
    'oe_media_circabc_test',
    'language',
    'content_translation',
    'views',
    'entity_browser',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['circabc'] = [
      'url' => 'https://example.com/circabc-ewpp',
      'category' => '1111',
      'username' => 'test',
      'password' => 'test',
    ];
    new Settings($settings);

    $this->installConfig([
      'oe_media_circabc',
      'language',
      'content_translation',
      'entity_browser',
    ]);

    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('pt-pt')->save();
  }

  /**
   * Tests that the Media is kept in sync with CircaBC (using the Pivot).
   */
  public function testPivotCircaBcSync(): void {
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');

    // Assert an exception is thrown if the UUID is missing.
    try {
      $media = $media_storage->create([
        'name' => 'a document media',
        'bundle' => 'document',
        'oe_media_file_type' => 'circabc',
      ]);
      $media->save();
      $this->fail('An exception was expected');
    }
    catch (\Exception $exception) {
      $this->assertEquals('Missing UUID', $exception->getMessage());
    }

    $media = $media_storage->create([
      'name' => 'a document media',
      'bundle' => 'document',
      'oe_media_file_type' => 'circabc',
      'oe_media_circabc_reference' => [
        // UUID is enough to start, it will pull all the rest of the data.
        'uuid' => 'e74e3bc0-a639-4e04-a839-3bbd60ed5688',
      ],
    ]);
    $media->save();

    $media_storage->resetCache();
    /** @var \Drupal\media\MediaInterface $media */
    $media = $media_storage->load($media->id());
    $reference = $media->get('oe_media_circabc_reference')->first()->getValue();
    $this->assertEquals('e74e3bc0-a639-4e04-a839-3bbd60ed5688', $reference['uuid']);
    $this->assertEquals('3028', $reference['size']);
    $this->assertEquals('application/pdf', $reference['mime']);
    $this->assertEquals('sample_pdf.pdf', $reference['filename']);
    $this->assertEquals('Test sample file', $media->label());
    $this->assertEquals('2023-10-25T05:55:00', (new \DateTime())->setTimestamp((int) $media->getCreatedTime())->setTimezone(new \DateTimeZone('UTC'))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
    $this->assertEquals('2023-10-26T05:55:00', (new \DateTime())->setTimestamp((int) $media->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));

    // Assert the translations (no translations as the media is not
    // translatable).
    $this->assertCount(1, $media->getTranslationLanguages());

    // Make the document media translatable and resave it.
    \Drupal::service('content_translation.manager')->setEnabled('media', 'document', TRUE);
    $media->save();
    $media_storage->resetCache();
    /** @var \Drupal\media\MediaInterface $media */
    $media = $media_storage->load($media->id());
    $this->assertCount(3, $media->getTranslationLanguages());
    $french = $media->getTranslation('fr');
    $reference = $french->get('oe_media_circabc_reference')->first()->getValue();
    // The reference field was not marked as translatable so the values are the
    // same.
    $this->assertEquals('e74e3bc0-a639-4e04-a839-3bbd60ed5688', $reference['uuid']);
    $this->assertEquals('3028', $reference['size']);
    $this->assertEquals('application/pdf', $reference['mime']);
    $this->assertEquals('sample_pdf.pdf', $reference['filename']);
    // The name is translatable.
    $this->assertEquals('Test sample file FR', $french->label());

    // Mark the reference field as translatable.
    $field = FieldConfig::load('media.document.oe_media_circabc_reference');
    $field->setTranslatable(TRUE);
    $field->save();

    // Resave the media and assert that the translations also got synced.
    $media->save();
    $media_storage->resetCache();
    /** @var \Drupal\media\MediaInterface $media */
    $media = $media_storage->load($media->id());
    $this->assertEquals('2023-10-25T05:55:00', (new \DateTime())->setTimestamp((int) $media->getCreatedTime())->setTimezone(new \DateTimeZone('UTC'))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
    $this->assertEquals('2023-10-26T05:55:00', (new \DateTime())->setTimestamp((int) $media->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
    $french = $media->getTranslation('fr');
    $this->assertEquals('Test sample file FR', $french->label());
    $reference = $french->get('oe_media_circabc_reference')->first()->getValue();
    $this->assertEquals('5d634abd-fec1-452a-ae0b-62e4cf080506', $reference['uuid']);
    $this->assertEquals('3028', $reference['size']);
    $this->assertEquals('application/pdf', $reference['mime']);
    $this->assertEquals('sample_pdf_FR.pdf', $reference['filename']);
    $this->assertTrue($media->getFieldDefinition('created')->isTranslatable());
    $this->assertTrue($media->getFieldDefinition('changed')->isTranslatable());
    $this->assertEquals('2023-10-23T05:55:00', (new \DateTime())->setTimestamp((int) $french->getCreatedTime())->setTimezone(new \DateTimeZone('UTC'))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
    $this->assertEquals('2023-10-27T08:05:00', (new \DateTime())->setTimestamp((int) $french->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));

    $portuguese = $media->getTranslation('pt-pt');
    $this->assertEquals('Test sample file PT', $portuguese->label());
    $reference = $portuguese->get('oe_media_circabc_reference')->first()->getValue();
    $this->assertEquals('78634abd-fec1-452a-ae0b-62e4cf080578', $reference['uuid']);
    $this->assertEquals('5028', $reference['size']);
    $this->assertEquals('application/pdf', $reference['mime']);
    $this->assertEquals('sample_pdf_PT.pdf', $reference['filename']);

    // Delete the translations and assert we don't re-sync it in the same
    // operation.
    $media->removeTranslation('fr');
    $media->removeTranslation('pt-pt');
    $media->save();
    $media_storage->resetCache();
    /** @var \Drupal\media\MediaInterface $media */
    $media = $media_storage->load($media->id());
    $this->assertCount(1, $media->getTranslationLanguages());

    // Resave the media and assert the translation is back.
    $media->save();
    $media_storage->resetCache();
    /** @var \Drupal\media\MediaInterface $media */
    $media = $media_storage->load($media->id());
    $this->assertCount(3, $media->getTranslationLanguages());
  }

  /**
   * Tests that the Media is kept in sync with CircaBC (using a translation).
   */
  public function testTranslationCircaBcSync(): void {
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');
    // Mark the documents as translatable.
    \Drupal::service('content_translation.manager')->setEnabled('media', 'document', TRUE);
    $field = FieldConfig::load('media.document.oe_media_circabc_reference');
    $field->setTranslatable(TRUE);
    $field->save();

    $media = $media_storage->create([
      'name' => 'a document media',
      'bundle' => 'document',
      'oe_media_file_type' => 'circabc',
      'oe_media_circabc_reference' => [
        // This is the UUID of a translation.
        'uuid' => '5d634abd-fec1-452a-ae0b-62e4cf080506',
      ],
    ]);
    $media->save();

    $media_storage->resetCache();
    /** @var \Drupal\media\MediaInterface $media */
    $media = $media_storage->load($media->id());
    $this->assertEquals('fr', $media->language()->getId());
    $reference = $media->get('oe_media_circabc_reference')->first()->getValue();
    $this->assertEquals('Test sample file FR', $media->label());
    $this->assertEquals('5d634abd-fec1-452a-ae0b-62e4cf080506', $reference['uuid']);
    $this->assertEquals('3028', $reference['size']);
    $this->assertEquals('application/pdf', $reference['mime']);
    $this->assertEquals('sample_pdf_FR.pdf', $reference['filename']);

    $translation = $media->getTranslation('en');
    $reference = $translation->get('oe_media_circabc_reference')->first()->getValue();
    $this->assertEquals('e74e3bc0-a639-4e04-a839-3bbd60ed5688', $reference['uuid']);
    $this->assertEquals('3028', $reference['size']);
    $this->assertEquals('application/pdf', $reference['mime']);
    $this->assertEquals('sample_pdf.pdf', $reference['filename']);
    $this->assertEquals('Test sample file', $translation->label());
  }

  /**
   * Tests the CircaBC view query plugin.
   */
  public function testCircaBcView(): void {
    $view = Views::getView('circabc_entity_browser');

    $expected = [
      'Test sample file',
      'Another doc',
      'Sample pdf',
    ];
    $this->assertViewResults($view, $expected);

    $view = Views::getView('circabc_entity_browser');
    $view->setExposedInput(['language' => 'fr']);
    $expected = [
      'Test sample file FR',
    ];
    $this->assertViewResults($view, $expected);

    $view = Views::getView('circabc_entity_browser');
    $view->setExposedInput(['language' => 'pt-pt']);
    $expected = [
      'Test sample file PT',
    ];
    $this->assertViewResults($view, $expected);

    $view = Views::getView('circabc_entity_browser');
    $view->setExposedInput(['interest_group' => '85a095a8-aacb-4ae2-9f67-c90a789e353e']);
    $expected = [
      'In different group',
    ];
    $this->assertViewResults($view, $expected);

    $view = Views::getView('circabc_entity_browser');
    $view->setExposedInput(['content_owner' => 'COMMU']);
    $expected = [
      'Test sample file',
    ];
    $this->assertViewResults($view, $expected);

    $view = Views::getView('circabc_entity_browser');
    $view->setExposedInput(['content_owner' => 'http://publications.europa.eu/resource/authority/corporate-body/DIGIT']);
    $expected = [
      'Test sample file',
    ];
    $this->assertViewResults($view, $expected);

    $view = Views::getView('circabc_entity_browser');
    $view->setExposedInput(['search' => 'sample']);
    $expected = [
      'Test sample file',
      'Sample pdf',
    ];
    $this->assertViewResults($view, $expected);

    $view = Views::getView('circabc_entity_browser');
    $view->setExposedInput(['language' => 'All']);
    $expected = [
      'Test sample file',
      'Another doc',
      'Sample pdf',
      'Test sample file FR',
      'Test sample file PT',
    ];
    $this->assertViewResults($view, $expected);

    $view = Views::getView('circabc_entity_browser');
    $view->setExposedInput(['language' => 'All', 'search' => 'sample']);
    $expected = [
      'Test sample file',
      'Sample pdf',
      'Test sample file FR',
      'Test sample file PT',
    ];
    $this->assertViewResults($view, $expected);

    $view = Views::getView('circabc_entity_browser');
    $view->setItemsPerPage(2);
    $view->setExposedInput(['language' => 'All']);
    $expected = [
      'Test sample file',
      'Another doc',
    ];
    $this->assertViewResults($view, $expected);

    $view = Views::getView('circabc_entity_browser');
    $view->setItemsPerPage(2);
    $view->setCurrentPage(1);
    $view->setExposedInput(['language' => 'All']);
    $expected = [
      'Sample pdf',
      'Test sample file FR',
    ];
    $this->assertViewResults($view, $expected);
  }

  /**
   * Tests that also non multilingual docs can be pulled.
   */
  public function testNonMultilingualDocument(): void {
    \Drupal::service('content_translation.manager')->setEnabled('media', 'document', TRUE);
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');
    $media = $media_storage->create([
      'name' => 'a document media',
      'bundle' => 'document',
      'oe_media_file_type' => 'circabc',
      'oe_media_circabc_reference' => [
        'uuid' => '664e3bc0-a639-4e04-a839-3bbd60ed5600',
      ],
    ]);
    $media->save();

    $media_storage->resetCache();
    /** @var \Drupal\media\MediaInterface $media */
    $media = $media_storage->load($media->id());
    $reference = $media->get('oe_media_circabc_reference')->first()->getValue();
    $this->assertEquals('664e3bc0-a639-4e04-a839-3bbd60ed5600', $reference['uuid']);

    // Assert the translations (no translations as the media is not
    // translatable).
    $this->assertCount(0, $media->getTranslationLanguages(FALSE));
  }

  /**
   * Tests that CircaBC langcodes are transformed to Drupal format.
   */
  public function testCircaBcLangcodeTransformation(): void {
    // Load a Portuguese document with "pt" langcode on CircaBC side.
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');
    $media = $media_storage->create([
      'name' => 'a PT document media',
      'bundle' => 'document',
      'oe_media_file_type' => 'circabc',
      'oe_media_circabc_reference' => [
        // UUID is enough to start, it will pull all the rest of the data.
        'uuid' => '6d634abd-fec1-452a-ae0b-62e4cf080506',
      ],
    ]);
    $media->save();

    // Expect the langcode to be "pt-pt" in Drupal.
    $media_storage->resetCache();
    /** @var \Drupal\media\MediaInterface $media */
    $media = $media_storage->load($media->id());
    $this->assertEquals('pt-pt', $media->language()->getId());
  }

  /**
   * Tests the CircaBC client service.
   */
  public function testCircaBcClient(): void {
    $client = \Drupal::service('oe_media_circabc.client');

    // Test fetching a document by UUID.
    $document = $client->getDocumentByUuid('e74e3bc0-a639-4e04-a839-3bbd60ed5688');
    $this->assertNotNull($document);
    $this->assertEquals('e74e3bc0-a639-4e04-a839-3bbd60ed5688', $document->getUuid());
    $this->assertEquals('Test sample file', $document->getTitle());
    $this->assertEquals('en', $document->getLangcode());

    // Test fetching a non-existing document by UUID.
    $document = $client->getDocumentByUuid('non-existing-uuid');
    $this->assertNull($document);

    // Test fetching a document by URL.
    $document = $client->getDocumentByUrl('https://example.com/circabc-ewpp/ui/group/85a095a8-aacb-4ae2-9f67-c90a789e353e/library/664e3bc0-a639-4e04-a839-3bbd60ed5600/details');
    $this->assertNotNull($document);
    $this->assertEquals('664e3bc0-a639-4e04-a839-3bbd60ed5600', $document->getUuid());
    $this->assertEquals('Non multilingual', $document->getTitle());
    $this->assertEquals('en', $document->getLangcode());

    // Test fetching a non-existing document by URL.
    $document = $client->getDocumentByUrl('https://example.com/circabc-ewpp/ui/group/85a095a8-aacb-4ae2-9f67-c90a789e353e/library/non-existent-uuid/details');
    $this->assertNull($document);

    // Test fetching a document with invalid URL.
    $document = $client->getDocumentByUrl('https://example.com/circabc-ewpp/ui/invalid-url');
    $this->assertNull($document);

    // Test fetching interest groups.
    $groups = $client->getInterestGroups();
    $this->assertNotEmpty($groups);
    $this->assertEquals('85a095a8-aacb-4ae2-9f67-c90a789e353e', $groups[0]['uuid']);
    $this->assertEquals('Group 1', $groups[0]['name']);

    // Test querying documents.
    $result = $client->query('1111', 'fr', 'sample', [], 1, 10);
    $this->assertEquals(1, $result->getTotal());
    $documents = $result->getDocuments();
    $this->assertCount(1, $documents);
    $this->assertEquals('5d634abd-fec1-452a-ae0b-62e4cf080506', $documents[0]->getUuid());
    $this->assertEquals('Test sample file FR', $documents[0]->getTitle());
    $this->assertEquals('fr', $documents[0]->getLangcode());

    // Test querying documents with no results.
    $result = $client->query('1111', 'de', 'non-existing-keyword', [], 1, 10);
    $this->assertEquals(0, $result->getTotal());
    $documents = $result->getDocuments();
    $this->assertCount(0, $documents);

    // Test querying documents in a specific interest group.
    $result = $client->query('85a095a8-aacb-4ae2-9f67-c90a789e353e', NULL, NULL, [], 1, 10);
    $this->assertEquals(1, $result->getTotal());
    $documents = $result->getDocuments();
    $this->assertCount(1, $documents);
    $this->assertEquals('004e3bc0-a639-4e04-a839-3bbd60ed5600', $documents[0]->getUuid());
    $this->assertEquals('In different group', $documents[0]->getTitle());

    // Test querying documents with content owner filter.
    $filters = [
      CircaBcClient::FILTER_CONTENT_OWNERS => ['DIGIT', 'COMMU'],
    ];
    $result = $client->query('1111', NULL, NULL, $filters, 1, 10);
    $this->assertEquals(1, $result->getTotal());
    $documents = $result->getDocuments();
    $this->assertCount(1, $documents);
    $this->assertEquals('e74e3bc0-a639-4e04-a839-3bbd60ed5688', $documents[0]->getUuid());
    $this->assertEquals('Test sample file', $documents[0]->getTitle());

    // Test querying documents with pagination.
    $result = $client->query('1111', NULL, NULL, [], 1, 2);
    $this->assertEquals(5, $result->getTotal());
    $documents = $result->getDocuments();
    $this->assertCount(2, $documents);
    $this->assertEquals('e74e3bc0-a639-4e04-a839-3bbd60ed5688', $documents[0]->getUuid());
    $this->assertEquals('Another doc', $documents[1]->getTitle());

    $result = $client->query('1111', NULL, NULL, [], 2, 2);
    $this->assertEquals(5, $result->getTotal());
    $documents = $result->getDocuments();
    $this->assertCount(2, $documents);
    $this->assertEquals('075cbd2b-b3c6-4e2f-a195-292af8980222', $documents[0]->getUuid());
    $this->assertEquals('Test sample file FR', $documents[1]->getTitle());

    // Test querying for modification date.
    $filter = [
      CircaBcClient::FILTER_MODIFIED_FROM => '2023-10-26',
    ];
    $result = $client->query('1111', NULL, NULL, $filter);
    $documents = $result->getDocuments();
    $this->assertCount(5, $documents);
    $this->assertEquals('e74e3bc0-a639-4e04-a839-3bbd60ed5688', $documents[0]->getUuid());

    $filter = [
      CircaBcClient::FILTER_MODIFIED_FROM => '2023-12-06',
      CircaBcClient::FILTER_MODIFIED_TO => '2024-01-01',
    ];
    $result = $client->query('1111', NULL, NULL, $filter);
    $documents = $result->getDocuments();
    $this->assertCount(4, $documents);
    $this->assertEquals('8d634abd-fec1-452a-ae0b-62e4cf080506', $documents[0]->getUuid());
  }

  /**
   * Executes a View.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to execute.
   * @param string $display_id
   *   The display id.
   *
   * @internal param string $display The display id*   The display id
   */
  protected function executeView(ViewExecutable $view, string $display_id): void {
    $view->setDisplay($display_id);
    $view->initQuery();
    $view->preExecute();
    $view->execute();
  }

  /**
   * Asserts the view results.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   * @param array $expected
   *   The expected results.
   */
  protected function assertViewResults(ViewExecutable $view, array $expected) {
    $this->executeView($view, 'entity_browser_1');
    $this->assertInstanceOf(CircaBcQuery::class, $view->query, 'Wrong query plugin used in the view.');
    $this->assertCount(count($expected), $view->result);
    $actual = [];
    foreach ($view->result as $result) {
      $actual[] = $result->title;
    }
    $this->assertEquals($expected, $actual);
  }

}
