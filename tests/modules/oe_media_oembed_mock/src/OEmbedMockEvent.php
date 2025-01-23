<?php

declare(strict_types=1);

namespace Drupal\oe_media_oembed_mock;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event used to collect the mocked JSON data.
 */
class OEmbedMockEvent extends Event {

  /**
   * Event name.
   */
  const OEMBED_MOCK_EVENT = 'oe_media_oembed_mock.event';

  /**
   * The Guzzle request.
   *
   * @var \Psr\Http\Message\RequestInterface
   */
  protected $request;

  /**
   * The resources JSON data.
   *
   * @var array
   */
  protected $resources;

  /**
   * The providers list.
   *
   * @var array
   */
  protected $providers;

  /**
   * OEmbedMockEvent constructor.
   *
   * @param array $resources
   *   The resources JSON data.
   */
  public function __construct(array $resources = []) {
    $this->resources = $resources;
  }

  /**
   * Getter.
   *
   * @return array
   *   The resources.
   */
  public function getResources(): array {
    return $this->resources;
  }

  /**
   * Setter.
   *
   * @param array $resources
   *   The resources.
   */
  public function setResources(array $resources): void {
    $this->resources = $resources;
  }

  /**
   * Getter.
   *
   * @return array
   *   The providers.
   */
  public function getProviders(): array {
    return $this->providers;
  }

  /**
   * Setter.
   *
   * @param array $providers
   *   The providers.
   */
  public function setProviders(array $providers): void {
    $this->providers = $providers;
  }

}
