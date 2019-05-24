/**
 * @file
 * Provides JavaScript additions to the WYSIWYG when using the media embed.
 */

(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.test = {
    attach: function (context) {
      CKEDITOR.on('instanceCreated', function(e) {
        e.editor.on('afterCommandExec', function (e) {
          if (e.data.name !== 'enter') {
            return;
          }

          let el = e.editor.getSelection().getStartElement();

          if (el.getName() === 'p' && el.hasAttribute('data-oembed') && el.hasPrevious() && el.getPrevious().hasAttribute('data-oembed')) {
            el.removeAttribute('data-oembed');
          }
        });
      });

    }
  }

})(jQuery, Drupal);
