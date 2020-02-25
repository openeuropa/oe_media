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
   * Configuration context class name.
   */
  const CONFIG_CONTEXT_CLASS = 'Drupal\DrupalExtension\Context\ConfigContext';

  /**
   * Keep track of medias so they can be cleaned up.
   *
   * @var array
   */
  protected $medias = [];

  /**
   * Keep track of files so they can be cleaned up.
   *
   * @var array
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
    $environment = $scope->getEnvironment();
    if ($environment->hasContextClass(self::CONFIG_CONTEXT_CLASS)) {
      $this->configContext = $environment->getContext(self::CONFIG_CONTEXT_CLASS);
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
   * @Given the following documents:
   */
  public function createMediaDocuments(TableNode $file_table): void {
    // Retrieve the url table from the test scenario.
    $files = $file_table->getColumnsHash();

    foreach ($files as $file_properties) {
      $file = $this->createFileEntity($file_properties['file']);

      $media = \Drupal::service('entity_type.manager')
        ->getStorage('media')->create([
          'bundle' => 'document',
          'name' => $file_properties['name'],
          'oe_media_file' => [
            'target_id' => (int) $file->id(),
          ],
          'status' => 1,
        ]);

      $media->save();

      // Store for cleanup.
      $this->medias[] = $media;
    }
  }

  /**
   * Creates media documents with the specified file names.
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
   * @Given the following images:
   */
  public function createMediaImages(TableNode $file_table): void {
    // Retrieve the url table from the test scenario.
    $files = $file_table->getColumnsHash();

    foreach ($files as $file_properties) {
      $file = $this->createFileEntity($file_properties['file']);

      $media = \Drupal::service('entity_type.manager')
        ->getStorage('media')->create([
          'bundle' => 'image',
          'name' => $file_properties['name'],
          'oe_media_image' => [
            'target_id' => (int) $file->id(),
            'alt' => $file_properties['alt'] ?? $file_properties['name'],
            'title' => $file_properties['title'] ?? $file_properties['name'],
          ],
          'status' => 1,
        ]);

      $media->save();

      // Store for cleanup.
      $this->medias[] = $media;
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
   * @Given the following AV Portal photos:
   */
  public function createMediaAvPortalPhotos(TableNode $url_table): void {
    // Retrieve the url table from the test scenario.
    $urls = $url_table->getColumnsHash();

    $pattern = '@audiovisual\.ec\.europa\.eu/(.*)/photo/(P\-.*\~2F.*)@i';

    foreach ($urls as $url) {
      $url = reset($url);

      preg_match_all($pattern, $url, $matches);
      if (empty($matches)) {
        continue;
      }

      // Converts the slash in the photo id.
      $photo_id = str_replace("~2F", "/", $matches[2][0]);

      $media = \Drupal::service('entity_type.manager')
        ->getStorage('media')->create([
          'bundle' => 'av_portal_photo',
          'oe_media_avportal_photo' => $photo_id,
          'status' => 1,
        ]);

      $media->save();

      // Store for cleanup.
      $this->medias[] = $media;
    }
  }

  /**
   * Remove any created media.
   *
   * @AfterScenario
   */
  public function cleanMedias(): void {
    if (empty($this->medias)) {
      return;
    }

    \Drupal::entityTypeManager()->getStorage('media')->delete($this->medias);
    $this->medias = [];
  }

  /**
   * Remove any created files.
   *
   * @AfterScenario
   */
  public function cleanFiles(): void {
    if (empty($this->files)) {
      return;
    }

    \Drupal::entityTypeManager()->getStorage('file')->delete($this->files);
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
      throw new \RuntimeException(sprintf('Configuration context not found, add %s to your contexts.', self::CONFIG_CONTEXT_CLASS));
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
    $file = file_save_data(file_get_contents($this->getMinkParameter('files_path') . $file_name), 'public://' . $file_name);
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
