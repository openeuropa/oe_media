/**
 * @file
 * Media embed plugin.
 */

(function ($, Drupal, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('embed_media', {
    // This plugin requires the Widgets System defined in the 'widget' plugin.
    requires: 'widget',

    // The plugin initialization logic goes inside this method.
    beforeInit: function (editor) {
      // Generic command for adding/editing entities of all types.
      editor.addCommand('editmedia', {
        allowedContent: 'p[data-oembed,data-embed-button,data-entity-uuid]',
        requiredContent: 'p[data-oembed,data-embed-button,data-entity-uuid]',
        modes: { wysiwyg : 1 },
        canUndo: true,
        exec: function (editor, data) {
          data = data || {};

          var existingElement = getSelectedEmbeddedEntity(editor);

          var existingValues = {};
          if (existingElement && existingElement.$ && existingElement.$.firstChild) {
            var embedDOMElement = existingElement.$.firstChild;
            // Populate array with the entity's current attributes.
            var attribute = null, attributeName;
            for (var key = 0; key < embedDOMElement.attributes.length; key++) {
              attribute = embedDOMElement.attributes.item(key);
              attributeName = attribute.nodeName.toLowerCase();
              if (attributeName.substring(0, 15) === 'data-cke-saved-') {
                continue;
              }
              existingValues[attributeName] = existingElement.data('cke-saved-' + attributeName) || attribute.nodeValue;
            }
          }

          var embed_button_id = data.id ? data.id : existingValues['data-embed-button'];

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
            editor.insertHtml(entityElement.getOuterHtml());
            // We hit Enter so that a new element can be embedded right after.
            editor.execCommand('enter', {'editorFocus': true});
            if (existingElement) {
              // Detach the behaviors that were attached when the entity content
              // was inserted.
              Drupal.runEmbedBehaviors('detach', existingElement.$);
              existingElement.remove();
            }
          };

          // Open the entity embed dialog for corresponding EmbedButton.
          Drupal.ckeditor.openDialog(editor, Drupal.url('media-embed/dialog/' + editor.config.drupal.format + '/' + embed_button_id), existingValues, saveCallback, dialogSettings);
        }
      });

      // Register the media embed widget.
      editor.widgets.add('media', {
        // Minimum HTML which is required by this widget to work.
        allowedContent: 'p[data-oembed,data-embed-button,data-entity-uuid]',
        requiredContent: 'p[data-oembed,data-embed-button,data-entity-uuid]',

        // Simply recognize the element as our own. The inner markup if fetched
        // and inserted the init() callback, since it requires the actual DOM
        // element.
        upcast: function (element) {
          var attributes = element.attributes;
          if (attributes['data-data-oembed'] === undefined) {
            return;
          }
          // Generate an ID for the element, so that we can use the Ajax
          // framework.
          element.attributes.id = generateEmbedId();
          return element;
        },

        // Fetch the rendered entity.
        init: function () {
          /** @type {CKEDITOR.dom.element} */
          var element = this.element;
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
          entityEmbedPreview.execute();
        },

        // Downcast the element.
        downcast: function (element) {
          // Only keep the wrapping element.
          element.setHtml('');
          // Remove the auto-generated ID.
          delete element.attributes.id;
          return element;
        }
      });

      // Register the toolbar buttons.
      if (editor.ui.addButton) {
        for (var key in editor.config.Media_buttons) {
          var button = editor.config.Media_buttons[key];
          editor.ui.addButton(button.id, {
            label: button.label,
            data: button,
            allowedContent: 'p[!data-oembed,!data-embed-button,!data-entity-uuid]',
            click: function(editor) {
              editor.execCommand('editmedia', this.data);
            },
            icon: button.image,
            modes: {wysiwyg: 1, source: 0}
          });
        }
      }

      // Register context menu items for editing widget.
      if (editor.contextMenu) {
        editor.addMenuGroup('media');

        for (var key in editor.config.Media_buttons) {
          var button = editor.config.Media_buttons[key];

          var label = Drupal.t('Edit @buttonLabel', { '@buttonLabel': button.label });

          editor.addMenuItem('media_' + button.id, {
            label: label,
            icon: button.image,
            command: 'editmedia',
            group: 'media'
          });
        }

        editor.contextMenu.addListener(function(element) {
          if (isEditableEntityWidget(editor, element)) {
            var button_id = element.getFirst().getAttribute('data-embed-button');
            var returnData = {};
            returnData['media_' + button_id] = CKEDITOR.TRISTATE_OFF;
            return returnData;
          }
        });
      }

      // Execute widget editing action on double click.
      editor.on('doubleclick', function (evt) {
        var element = getSelectedEmbeddedEntity(editor) || evt.data.element;

        if (isEditableEntityWidget(editor, element)) {
          editor.execCommand('editmedia');
        }
      });
    }
  });

  /**
   * Get the surrounding media widget element.
   *
   * @param {CKEDITOR.editor} editor
   */
  function getSelectedEmbeddedEntity(editor) {
    var selection = editor.getSelection();
    var selectedElement = selection.getSelectedElement();
    if (isEditableEntityWidget(editor, selectedElement)) {
      return selectedElement;
    }

    return null;
  }

  /**
   * Checks if the given element is an editable media widget.
   *
   * @param {CKEDITOR.editor} editor
   * @param {CKEDITOR.htmlParser.element} element
   */
  function isEditableEntityWidget (editor, element) {
    var widget = editor.widgets.getByElement(element, true);
    if (!widget || widget.name !== 'media') {
      return false;
    }

    var button = $(element.$.firstChild).attr('data-embed-button');
    if (!button) {
      // If there was no data-embed-button attribute, not editable.
      return false;
    }

    // The button itself must be valid.
    return editor.config.Media_buttons.hasOwnProperty(button);
  }

  /**
   * Generates unique HTML IDs for the widgets.
   *
   * @returns {string}
   */
  function generateEmbedId() {
    if (typeof generateEmbedId.counter == 'undefined') {
      generateEmbedId.counter = 0;
    }
    return 'media-embed-' + generateEmbedId.counter++;
  }

})(jQuery, Drupal, CKEDITOR);
