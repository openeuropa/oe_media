/**
 * @file
 * Media embed plugin.
 */

(function ($, Drupal, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('embed_media', {
    // This plugin requires the Widgets System defined in the 'widget' plugin.
    requires: 'widget',

    beforeInit: function (editor) {

      // Generic command for adding media entities.
      editor.addCommand('editmedia', {
        allowedContent: 'p[data-oembed]',
        requiredContent: 'p[data-oembed]',
        modes: { wysiwyg : 1 },
        canUndo: true,
        exec: function (editor) {
          let dialogSettings = {
            dialogClass: 'media-select-dialog',
            resizable: false
          };

          let saveCallback = function (values) {
            let entityElement = editor.document.createElement('p');
            let attributes = values.attributes;
            for (let key in attributes) {
              if (['data-resource-url', 'data-resource-label'].includes(key)) {
                continue;
              }
              entityElement.setAttribute(key, attributes[key]);
            }

            let childElement = editor.document.createElement('a');
            childElement.setAttribute('href', attributes['data-resource-url']);
            childElement.setHtml(attributes['data-resource-label']);
            entityElement.setHtml(childElement.getOuterHtml());
            editor.insertHtml(entityElement.getOuterHtml() + '<p></p>');
          };

          // Open the dialog to look for a media entity.
          Drupal.ckeditor.openDialog(editor, Drupal.url('media-embed/dialog/' + editor.config.drupal.format + '/media'), {}, saveCallback, dialogSettings);
        }
      });

      // Register the media embed widget.
      editor.widgets.add('media', {
        allowedContent: 'p[data-oembed]',

        // Upcasts the embedded element to be treated as a widget by CKEditor.
        upcast: function (element, data) {
          let attributes = element.attributes;
          if (attributes['data-oembed'] === undefined) {
            return;
          }

          // Generate an ID for the element, so that we can use the Ajax
          // framework later when we need to render inside the init() method.
          element.attributes.id = generateEmbedId();
          return element;
        },

        // Fetch the rendered entity.
        init: function () {
          // @todo fetch the rendered tag once we actually have a filter in
          // place.
        },

        // Downcast the element.
        downcast: function (element) {
          // Remove the auto-generated ID.
          delete element.attributes.id;
          return element;
        }
      });

      // Register the toolbar button.
      if (editor.ui.addButton) {
        let button = editor.config.Media_buttons['media'];
        editor.ui.addButton(button.id, {
          label: button.label,
          data: button,
          allowedContent: 'p[!data-oembed]',
          click: function(editor) {
            editor.execCommand('editmedia', this.data);
          },
          icon: button.image,
          modes: {wysiwyg: 1, source: 0}
        });
      }
    }
  });

  /**
   * Generates unique HTML IDs for the widgets.
   *
   * @returns {string}
   */
  function generateEmbedId() {
    if (typeof generateEmbedId.counter === 'undefined') {
      generateEmbedId.counter = 0;
    }
    return 'media-embed-' + generateEmbedId.counter++;
  }

})(jQuery, Drupal, CKEDITOR);
