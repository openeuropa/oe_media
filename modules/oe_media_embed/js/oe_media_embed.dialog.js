/**
 * @file
 * Provides JavaScript additions to media embed dialog.
 *
 * This file provides popup windows for previewing embedded media from the
 * embed dialog.
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Attach behaviors to links for entities.
   */
  Drupal.behaviors.mediaEmbedPreviewEntities = {
    attach: function (context) {
      $(context).find('form.media-embed-dialog .form-item-entity a').on('click', Drupal.mediaEmbedDialog.openInNewWindow);
    },
    detach: function (context) {
      $(context).find('form.media-embed-dialog .form-item-entity a').off('click', Drupal.mediaEmbedDialog.openInNewWindow);
    }
  };

  /**
   * Behaviors for the mediaEmbedDialog iframe.
   */
  Drupal.behaviors.mediaEmbedDialog = {
    attach: function (context, settings) {
      $('body').once('js-media-embed-dialog').on('entityBrowserIFrameAppend', function () {
        $('.media-select-dialog').trigger('resize');
        // Hide the next button, the click is triggered by Drupal.mediaEmbedDialog.selectionCompleted.
        $('#drupal-modal').parent().find('.js-button-next').addClass('visually-hidden');
      });
    }
  };

  /**
   * Media Embed dialog utility functions.
   */
  Drupal.mediaEmbedDialog = Drupal.mediaEmbedDialog || {

    /**
     * Open links to entities within forms in a new window.
     */
    openInNewWindow: function (event) {
      event.preventDefault();
      $(this).attr('target', '_blank');
      window.open(this.href, 'entityPreview', 'toolbar=0,scrollbars=1,location=1,statusbar=1,menubar=0,resizable=1');
    },
    selectionCompleted: function(event, uuid, entities) {
      $('.media-select-dialog .js-button-next').click();
    }
  };

})(jQuery, Drupal);
