/**
 * @file
 * Media embed plugin.
 */

(function ($, Drupal, CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add('embed_media', {
    // This plugin requires the Widgets System defined in the 'widget' plugin.
    requires: 'widget',

    beforeInit: function (editor) {

      // Generic command for adding media entities.
      editor.addCommand('editmedia', {
        allowedContent: 'p[data-oembed]',
        requiredContent: 'p[data-oembed]',
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor) {
          var dialogSettings = {
            dialogClass: 'media-select-dialog',
            resizable: false
          };

          var saveCallback = function (values) {
            var entityElement = editor.document.createElement('p');
            var attributes = values.attributes;
            for (var key in attributes) {
              if (['data-resource-url', 'data-resource-label'].includes(key)) {
                continue;
              }
              entityElement.setAttribute(key, attributes[key]);
            }

            var childElement = editor.document.createElement('a');
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
      editor.widgets.add('embed_media', {
        allowedContent: 'p[data-oembed]',
        requiredContent: 'p[data-oembed]',

        // Upcasts the embedded element to be treated as a widget by CKEditor.
        upcast: function (element, data) {
          var attributes = element.attributes;
          if (typeof attributes['data-oembed'] === 'undefined') {
            return;
          }

          // Generate an ID for the element, so that we can use the Ajax
          // framework later when we need to render inside the init() method.
          element.attributes.id = generateEmbedId();
          return element;
        },

        // Fetch the rendered entity.
        init: function () {
          /** @type {CKEDITOR.dom.element} */
          // @todo: Use this code once we need to render the media with a defined display mode in WYSIWYG.
          /**var element = this.element;
          // Use the Ajax framework to fetch the HTML, so that we can retrieve
          // out-of-band assets (JS, CSS...).
          var entityEmbedPreview = Drupal.ajax({
            base: element.getId(),
            element: element.$,
            url: Drupal.url('embed/preview/' + editor.config.drupal.format + '?' + $.param({
              value: element.getOuterHtml()
            })),
            progress: {type: 'none'},
            // Use a custom event to trigger the call.
            event: 'entity_embed_dummy_event'
          });
          entityEmbedPreview.execute();*/
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
        var button = editor.config.Media_buttons['media'];
        editor.ui.addButton(button.id, {
          label: button.label,
          data: button,
          allowedContent: 'p[!data-oembed]',
          click: function (editor) {
            editor.execCommand('editmedia', this.data);
          },
          icon: button.image,
        });
      }
    }
  });

  /**
   * Generates unique HTML IDs for the widgets.
   *
   * @return {string}
   *   A unique HTML ID.
   */
  function generateEmbedId() {
    if (typeof generateEmbedId.counter === 'undefined') {
      generateEmbedId.counter = 0;
    }
    return 'media-embed-' + generateEmbedId.counter++;
  }

})(jQuery, Drupal, CKEDITOR);
