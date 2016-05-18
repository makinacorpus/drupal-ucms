/* jshint forin: false, strict: true, browser: true,  jquery: true */
/* globals CKEDITOR, Drupal, jQuery */
(function (CKEDITOR, Drupal, $) {
  "use strict";

  // Seriously, fuck you hard, javascript. Die in hell, burn in hell. I hate
  // you, Chrome developers, I hate you Firefox developers, and I hate you
  // most all JavaScript evangelists. You should have done an IT school before
  // writing any of this, seriously. Go to hell everyone.

  // The only one of you I love is the one that wrote jshint.com

  // And by the way, you will need this to work gracefully:
  // extraAllowedContent: 'div[*];img{width,height}[style,src,srcset,media,sizes];picture{width,height}[style];source[src,srcset,media,sizes];'

  /**
   * CKEDITOR instance ready event listener, find deeper div within the editor
   * just on top the iframe, and make it being a jQuery.ui.droppable instance
   */
  function onInstanceReady(editorEvent) {

    // This should do the trick
    var parent = $(editorEvent.editor.ui.contentsElement.$).find('iframe').parent();
    if (!parent.length) {
      // We are in <div> mode...
      parent = $(editorEvent.editor.ui.contentsElement.$);
    }

    try {
      $(parent).droppable('destroy');
    } catch (e) {
      // Might not be initialized yet
    }

    // Ok, we are going to be UGLYYYYYYYYYYYYYY.
    $(parent).droppable({
      // seb, put here the class you want.
      //activeClass: "ckeditor-allow-drop",
      tolerance: "pointer",
      // I keep this warm, the jquery.ui.droppable iframe fix does not work, but
      // we reproduced it using CSS instead, it allows us to ensure that we do
      // not trigger the draggable iframe bug (dragging over an iframe freezes
      // the browser).
      //iframeFix: true,
      accept: function ($item) {
        // Prevent wrong bundles.
        // return $.inArray($item.data('bundle'), allowedBundles) > -1;
        return true;
      },
      drop: function (event, ui) {
        var nid = ui.draggable.data('nid');
        if (!nid) {
          return;
        }
        CKEDITOR.ajax.load('/node/' + nid + '/ajax', function (data) {
          data = JSON.parse(data);
          if (data && data.output) {
            createAndPlaceMedia(editorEvent.editor, nid, data.output);
          }
        });
      }
    });
  }

  /**
   * Magical function taht gets the raw HTML content that reprensents the media
   * and inject it into the editor, simple and efficient
   *
   * @param CKEDITOR.editor editor
   * @param int nid
   * @param string content
   */
  function createAndPlaceMedia (editor, nid, content) {

    // content must be converted to anything that is dom-ish, the more I refine
    // this widget, the less I use jQuery into all of this, it justs brings an
    // awful lot of bugs.
    var element = CKEDITOR.dom.element.createFromHtml(content);
    element.setAttribute('data-media-nid', nid);
    editor.insertElement(element);

    // and finally, CKEditor is not that bad, once you read the doc
    // http://docs.ckeditor.com/#!/api/CKEDITOR.plugins.widget
    editor.widgets.initOn(element, 'ucmsmedia');
    // this call is not mandatory, but it forces a refresh, it allowed us to
    // detect bugs that were otherwise triggered on POST and almost invisible
    editor.updateElement();
  }

  CKEDITOR.plugins.add('ucmsmediadnd', {
    requires: 'contextmenu,widget,ajax',

    init: function (editor) {

      // Attach external dnd listener
      editor.on('instanceReady', onInstanceReady);

      // For later, anyone trying to change or fix this code, please first read:
      // http://docs.ckeditor.com/#!/guide/widget_sdk_tutorial_1
      editor.widgets.add('ucmsmedia', {

        // widget with <div/> cannot be made inline, this triggers sad ckeditor
        // bugs and will make the getData() event to break, took me a while to
        // find it out:
        // http://ckeditor.com/forums/Support/inline-widget-error-on-downcast
        // https://github.com/ckeditor/ckeditor-releases/issues/16
        inline: false,
        draggable: true,

        // @todo I think there is a serious problem here, when our content is
        // already within the HTML, or when moving a widget, CKEditor fucks it
        // up and removes the surrounding divs...
        // VERY IMPORTANT: this needs the global editor ACF configuration to
        // allow arbitrary div elements!
        allowedContent: '*[*]{*}(*)',
        requiredContent: 'div[data-media-nid]',
        template: '<div data-media-nid=some></div>',

        // Believe this or not, but we don't need any data, but if we don't set
        // it at some point, our element is empty, and has no data, it may break
        // CKE at some point, for an unknown and deep totally non-legit reason,
        // even if I could not reproduce the bug since, let's keep this
        init: function () {
          this.setData('data-media-nid', this.element.getAttribute('data-media-nid'));
        },

        // Allow the editor to match our raw html as a widget instance
        // http://docs.ckeditor.com/#!/api/CKEDITOR.plugins.widget.definition-property-upcast
        upcast: function (element) {
          return element.name == 'div' && element.attributes['data-media-nid'];
        }
      });

      // Since we can't inline our widget, we are going to provide a simple
      // helper for the users to be able to float left or right their widgets
      // http://docs.ckeditor.com/#!/guide/plugin_sdk_sample_2
      if (editor.contextMenu) {

        // All three commands will set the float classes on both the wrapper and
        // the inner div, this is necessary somehow because we do need it to
        // really float in both the editor (wrapper class) and the final text
        // looses the wrapper (the inside div)
        editor.addCommand('ucmsMediaLeft', new CKEDITOR.command(editor, {
          canUndo: true,
          exec: function (editor) {
            var element = editor.getSelection().getStartElement();
            if (element) {
              var widget = editor.widgets.getByElement(element);
              element.addClass('pull-left');
              widget.element.addClass('pull-left');
            }
          }
        }));
        editor.addCommand('ucmsMediaRight', new CKEDITOR.command(editor, {
          canUndo: true,
          exec: function (editor) {
            var element = editor.getSelection().getStartElement();
            if (element) {
              var widget = editor.widgets.getByElement(element);
              element.addClass('pull-right');
              widget.element.addClass('pull-right');
            }
          }
        }));
        editor.addCommand('ucmsMediaNone', new CKEDITOR.command(editor, {
          canUndo: true,
          exec: function (editor) {
            var element = editor.getSelection().getStartElement();
            if (element) {
              var widget = editor.widgets.getByElement(element);
              element.removeClass('pull-left');
              element.removeClass('pull-right');
              widget.element.removeClass('pull-left');
              widget.element.removeClass('pull-right');
            }
          }
        }));

        editor.addMenuGroup('ucmsMediaGroup');
        editor.addMenuItem('ucmsMediaLeft', {
          label: 'Float left',
          icon: this.path + 'icons/left.png',
          command: 'ucmsMediaLeft',
          group: 'ucmsMediaGroup'
        });
        editor.addMenuItem('ucmsMediaRight', {
          label: 'Float right',
          icon: this.path + 'icons/right.png',
          command: 'ucmsMediaRight',
          group: 'ucmsMediaGroup'
        });
        editor.addMenuItem('ucmsMediaNone', {
          label: 'Remove float',
          icon: this.path + 'icons/none.png',
          command: 'ucmsMediaNone',
          group: 'ucmsMediaGroup'
        });

        editor.contextMenu.addListener(function (element) {
          var widget = editor.widgets.getByElement(element);
          if (widget && "ucmsmedia" === widget.name) {
            return {
              ucmsMediaLeft: CKEDITOR.TRISTATE_OFF,
              ucmsMediaRight: CKEDITOR.TRISTATE_OFF,
              ucmsMediaNone: CKEDITOR.TRISTATE_OFF
            };
          }
        });
      }
    }
  });

}(CKEDITOR, Drupal, jQuery));