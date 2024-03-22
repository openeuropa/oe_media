<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media_circabc\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\oe_media\Kernel\MediaTestBase;

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
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'oe_media_circabc',
      'language',
      'content_translation',
    ]);

    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['circabc'] = [
      'url' => 'https://example.com/circabc-ewpp',
    ];
    new Settings($settings);

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
    $french = $media->getTranslation('fr');
    $this->assertEquals('Test sample file FR', $french->label());
    $reference = $french->get('oe_media_circabc_reference')->first()->getValue();
    $this->assertEquals('5d634abd-fec1-452a-ae0b-62e4cf080506', $reference['uuid']);
    $this->assertEquals('3028', $reference['size']);
    $this->assertEquals('application/pdf', $reference['mime']);
    $this->assertEquals('sample_pdf_FR.pdf', $reference['filename']);

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

}
