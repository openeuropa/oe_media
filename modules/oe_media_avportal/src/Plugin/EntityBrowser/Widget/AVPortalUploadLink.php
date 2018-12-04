<?php

declare(strict_types = 1);

namespace Drupal\oe_media_avportal\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\entity_browser\WidgetBase;

/**
 * Entity browser widget linking to the AV Portal service for uploading videos.
 *
 * @EntityBrowserWidget(
 *   id = "av_portal_upload_link",
 *   label = @Translation("AVPortal Upload"),
 *   description = @Translation("Upload widget that links to AVPortal."),
 *   auto_select = FALSE
 * )
 */
class AVPortalUploadLink extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $aditional_widget_parameters) {

    $form['upload'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('AV Portal External upload'),
    ];

    $link = Link::fromTextAndUrl($this->t('external link'), Url::fromUri('https://webgate.ec.europa.eu/europa-hub/en/vplay/add', ['attributes' => ['target' => '_blank']]))->toString();

    $form['upload']['markup'] = [
      '#markup' => $this->t('Videos can be uploaded to AV Portal at the following @link.', ['@link' => $link]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Fake support to entities.
   *
   * We need to implement this abstract method but it's not actually used
   * because we are not dealing with any entities.
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    return [];
  }

}
