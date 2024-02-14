<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_media\Kernel;

use Drupal\file\Entity\File;
use Drupal\media\MediaInterface;

/**
 * Tests the document media type.
 */
class DocumentMediaTest extends MediaTestBase {

  /**
   * Tests that the correct field keeps the value depending on the type.
   */
  public function testDocumentMediaValues(): void {
    $this->container->get('file_system')->copy(
      \Drupal::service('extension.list.module')->getPath('oe_media') . '/tests/fixtures/sample.pdf',
      'public://sample.pdf'
    );
    $file = File::create([
      'uri' => 'public://sample.pdf',
    ]);
    $file->save();

    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');

    /** @var \Drupal\media\MediaInterface $media */
    $media = $media_storage->create([
      'name' => 'a document media',
      'bundle' => 'document',
    ]);

    // Tests that document media entities validate correctly.
    $this->assertViolation($media, 'The document file type is missing.');
    $media->set('oe_media_file_type', 'local');
    $media->set('oe_media_remote_file', 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf');
    $this->assertViolation($media, 'The document is configured to be local, please upload a local file.');

    $media = $media_storage->create([
      'name' => 'a document media',
      'bundle' => 'document',
    ]);
    $media->set('oe_media_file_type', 'remote');
    $media->set('oe_media_file', $file);
    $this->assertViolation($media, 'The document is configured to be remote, please reference a remote file.');

    // Test that when setting the local file, the remote file link is removed.
    $media->set('oe_media_file_type', 'local');
    $media->set('oe_media_remote_file', 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf');
    $media->set('oe_media_file', $file);
    $media->save();

    $media_storage->resetCache();
    /** @var \Drupal\media\MediaInterface $media */
    $media = $media_storage->load($media->id());
    $this->assertTrue($media->get('oe_media_remote_file')->isEmpty());
    $this->assertEquals($file->id(), $media->get('oe_media_file')->entity->id());

    // Update the media and change to remote file.
    $media->set('oe_media_file_type', 'remote');
    $media->set('oe_media_remote_file', 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf');
    $media->set('oe_media_file', $file);
    $media->save();

    $media_storage->resetCache();
    /** @var \Drupal\media\MediaInterface $media */
    $media = $media_storage->load($media->id());
    $this->assertTrue($media->get('oe_media_file')->isEmpty());
    $this->assertEquals('https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf', $media->get('oe_media_remote_file')->uri);
  }

  /**
   * Asserts that the media entity fails validation with a specific message.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   * @param string $violation_message
   *   The expected message.
   */
  protected function assertViolation(MediaInterface $media, string $violation_message): void {
    $violations = $media->validate();
    $messages = [];
    foreach ($violations as $violation) {
      $messages[] = $violation->getMessage()->__toString();
    }
    $this->assertContains($violation_message, $messages);
  }

}
