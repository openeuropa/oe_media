<?php

declare(strict_types=1);

namespace Drupal\oe_media_circabc;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Use our own media form handler for documents.
 */
class OeMediaCircabcServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('oe_media.document_media_form_handler')) {
      $definition = $container->getDefinition('oe_media.document_media_form_handler');
      $definition->setClass(DocumentMediaFormHandler::class);
    }
  }

}
