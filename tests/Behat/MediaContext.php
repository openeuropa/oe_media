<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\ConfigContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;

/**
 * Context to related to media testing.
 */
class MediaContext extends RawDrupalContext {

  /**
   * Keep track of medias so they can be cleaned up.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected $media = [];

  /**
   * Keep track of files so they can be cleaned up.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected $files = [];

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
  public function gatherContexts(BeforeScenarioScope $scope): void {
    // We check if the current Behat environment has the given context.
    // This is necessary as this context can be reused on external projects.
    $environment = $scope->getEnvironment();
    if ($environment->hasContextClass(ConfigContext::class)) {
      $this->configContext = $environment->getContext(ConfigContext::class);
    }
  }

  /**
   * Enables the Mock for remote videos.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The scope.
   *
   * @beforeScenario @remote-video
   */
  public function enableTestModule(BeforeScenarioScope $scope): void {
    \Drupal::service('module_installer')->install(['oe_media_oembed_mock']);
  }

  /**
   * Disables the Mock for remote videos.
   *
   * @param \Behat\Behat\Hook\Scope\AfterScenarioScope $scope
   *   The scope.
   *
   * @afterScenario @remote-video
   */
  public function disableTestModule(AfterScenarioScope $scope): void {
    \Drupal::service('module_installer')->uninstall(['oe_media_oembed_mock']);
  }

  /**
   * Enables standalone url for media entities.
   *
   * @beforeScenario @media-enable-standalone-url
   */
  public function enableMediaStandaloneUrl(BeforeScenarioScope $scope): void {
    $this->getConfigContext()->setConfig('media.settings', 'standalone_url', TRUE);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Navigates to the canonical page of a media entity.
   *
   * @param string $name
   *   The title of the media.
   *
   * @When (I )go to the :name media page
   * @When (I )visit the :name media page
   */
  public function visitMediaPage(string $name): void {
    $media = $this->getMediaByName($name);
    $this->visitPath($media->toUrl()->toString());
  }

  /**
   * Creates media documents with the specified file names.
   *
   * Usage example:
   *
   * Given the following documents:
   *   | name   | file   |
   *   | name 1 | file 1 |
   *   |   ...  |   ...  |
   *
   * @Given the following document(s):
   */
  public function createMediaLocalDocuments(TableNode $table): void {
    // Retrieve the url table from the test scenario.
    $files = $table->getColumnsHash();
    foreach ($files as $properties) {
      $file = $this->createFileEntity($properties['file']);
      $media = \Drupal::entityTypeManager()
        ->getStorage('media')->create([
          'bundle' => 'document',
          'name' => $properties['name'],
          'oe_media_file_type' => 'local',
          'oe_media_file' => [
            'target_id' => (int) $file->id(),
          ],
          'status' => 1,
        ]);
      $media->save();

      // Store for cleanup.
      $this->media[] = $media;
    }
  }

  /**
   * Creates media images with the specified field values.
   *
   * Usage example:
   *
   * Given the following images:
   *   | name   | file   | alt   | title   |
   *   | name 1 | file 1 | alt 1 | title 1 |
   *   | ...    | ...    | ...   | ...     |
   *
   * Properties "alt" and "title" are optional, "name" will be used if they
   * are not provided.
   *
   * @Given the following image(s):
   */
  public function createMediaImages(TableNode $table): void {
    // Retrieve the url table from the test scenario.
    $files = $table->getColumnsHash();
    foreach ($files as $properties) {
      $file = $this->createFileEntity($properties['file']);
      $media = \Drupal::entityTypeManager()
        ->getStorage('media')->create([
          'bundle' => 'image',
          'name' => $properties['name'],
          'oe_media_image' => [
            'target_id' => (int) $file->id(),
            'alt' => $properties['alt'] ?? $properties['name'],
            'title' => $properties['title'] ?? $properties['name'],
          ],
          'status' => 1,
        ]);
      $media->save();

      // Store for cleanup.
      $this->media[] = $media;
    }
  }

  /**
   * Creates media AVPortal photos with the specified URLs.
   *
   * Usage example:
   *
   * Given the following AV Portal photos:
   *   | url   |
   *   | url 1 |
   *   |  ...  |
   *
   * @Given the following AV Portal photo(s):
   */
  public function createMediaAvPortalPhotos(TableNode $table): void {
    /** @var \Drupal\media_avportal\Plugin\media\Source\MediaAvPortalSourceInterface $media_source */
    $media_source = \Drupal::entityTypeManager()
      ->getStorage('media_type')
      ->load('av_portal_photo')
      ->getSource();

    // Retrieve the url table from the test scenario.
    foreach ($table->getColumnsHash() as $hash) {
      $media = \Drupal::entityTypeManager()
        ->getStorage('media')->create([
          'bundle' => 'av_portal_photo',
          'oe_media_avportal_photo' => $media_source->transformUrlToReference($hash['url']),
          'status' => 1,
        ]);
      $media->save();

      // Store for cleanup.
      $this->media[] = $media;
    }
  }

  /**
   * Creates media Remote video with the specified URLs.
   *
   * Usage example:
   *
   * Given the following remote videos:
   *   | url   |
   *   | url 1 |
   *   |  ...  |
   *
   * @Given the following remote video(s):
   */
  public function createMediaRemoteVideo(TableNode $table): void {
    foreach ($table->getColumnsHash() as $hash) {
      $media = \Drupal::entityTypeManager()
        ->getStorage('media')->create([
          'bundle' => 'remote_video',
          'oe_media_oembed_video' => $hash['url'],
          'status' => 1,
        ]);
      $media->save();

      // Store for cleanup.
      $this->media[] = $media;
    }
  }

  /**
   * Remove any created media.
   *
   * @AfterScenario
   */
  public function cleanMedias(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('media');
    foreach ($this->media as $media) {
      // Tests might manually delete entities created via this step.
      // Here we check if the entity still exists before deleting it.
      if ($storage->load($media->id()) !== NULL) {
        $media->delete();
      }
    }

    // Reset entity array.
    $this->media = [];
  }

  /**
   * Remove any created files.
   *
   * @AfterScenario
   */
  public function cleanFiles(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('file');
    foreach ($this->files as $file) {
      // Tests might manually delete entities created via this step.
      // Here we check if the entity still exists before deleting it.
      if ($storage->load($file->id()) !== NULL) {
        $file->delete();
      }
    }

    // Reset entity array.
    $this->files = [];
  }

  /**
   * Get config context object, if any.
   *
   * @return \Drupal\DrupalExtension\Context\ConfigContext
   *   Config context object.
   */
  protected function getConfigContext(): ConfigContext {
    if (!$this->configContext) {
      throw new \RuntimeException(sprintf('Configuration context not found, add %s to your contexts.', ConfigContext::class));
    }
    return $this->configContext;
  }

  /**
   * Create a file entity from given file and return its object.
   *
   * @param string $file_name
   *   File name, relative to Mink 'files_path' location.
   *
   * @return \Drupal\file\FileInterface
   *   File entity object.
   */
  protected function createFileEntity(string $file_name): FileInterface {
    $file = file_save_data(file_get_contents($this->getMinkParameter('files_path') . $file_name), 'public://' . basename($file_name));
    $file->setPermanent();
    $file->save();

    // Store for cleanup.
    $this->files[] = $file;

    return $file;
  }

  /**
   * Retrieves a media entity by its name.
   *
   * @param string $name
   *   The media name.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   */
  protected function getMediaByName(string $name): MediaInterface {
    $storage = \Drupal::entityTypeManager()->getStorage('media');
    $media = $storage->loadByProperties([
      'name' => $name,
    ]);

    if (!$media) {
      throw new \Exception("Could not find media with name '$name'.");
    }

    if (count($media) > 1) {
      throw new \Exception("Multiple medias with name '$name' found.");
    }

    return reset($media);
  }

  /**
   * Try to download file.
   *
   * @param string $media_name
   *   Name of the media.
   *
   * @When I try to download the :media_title media file
   */
  public function iTryToDownloadMediaFile(string $media_name): void {
    $storage = \Drupal::entityTypeManager()->getStorage('media');
    /** @var \Drupal\media\Entity\Media $media */
    $medias = $storage->loadByProperties([
      'name' => $media_name,
    ]);

    if (!$medias) {
      throw new \Exception("Could not find media with name '$media_name'.");
    }

    if (count($medias) > 1) {
      throw new \Exception("Multiple medias with name '$media_name' found.");
    }

    $media = reset($medias);

    $source_field = $media->get($media->getSource()->getConfiguration()['source_field']);
    $file_url = file_create_url($source_field->entity->getFileUri());
    $this->visitPath(file_url_transform_relative($file_url));
  }

}
