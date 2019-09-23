<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_media\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\media\MediaInterface;

/**
 * Context to related to media testing.
 */
class MediaContext extends RawDrupalContext {

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
    $this->configContext->setConfig('media.settings', 'standalone_url', TRUE);
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
