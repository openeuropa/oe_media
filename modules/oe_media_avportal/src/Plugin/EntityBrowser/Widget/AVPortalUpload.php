<?php

declare(strict_types = 1);

namespace Drupal\oe_media_avportal\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\entity_browser\WidgetBase;

/**
 * Uses upload to create media images.
 *
 * @EntityBrowserWidget(
 *   id = "avportal_upload",
 *   label = @Translation("AVPortal Upload"),
 *   description = @Translation("Upload widget that links to AVPortal."),
 *   auto_select = FALSE
 * )
 */
class AVPortalUpload extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'media_type' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $aditional_widget_parameters) {

    $form['upload'] = [
      '#type' => 'fieldset',
      '#title' => 'External upload',
    ];

    $link = Link::fromTextAndUrl(t('external link'), Url::fromUri('https://webgate.ec.europa.eu/europa-hub/en/vplay/add', ['attributes' => ['target' => '_blank']]))->toString();

    $form['upload']['markup'] = [
      '#markup' => $this->t('Media assets can be registered to AVPortal in the following %link', ['%link' => $link]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    return [];
  }

}
