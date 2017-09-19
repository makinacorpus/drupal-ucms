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
   * Fetch Drupal rendered node and replace innerHTML of the given element
   *
   * @param CKEDITOR.editor editor
   * @param int nid
   * @param CKEDITOR.dom.element parent
   */
  function fetchAndReplaceContent(editor, nid, parent) {
    if (!nid) {
      return;
    }
    CKEDITOR.ajax.load('/node/' + nid + '/ajax', function (data) {
      data = JSON.parse(data);
      if (data && data.output && "string" === typeof data.output) {
        parent.$.innerHTML = data.output;
      } else {
        if (console) {
          parent.$.innerHTML = "Loading the element failed";
          console.log("error while rendering element, wrong content loaded from ajax");
        }
      }
    });
  }

  /**
   * Create media element prior to inclusion in the editor
   *
   * @param CKEDITOR.editor editor
   * @param int nid
   * @param string content
   *
   * @return CKEDITOR.dom.element
   */
  function createMediaElement(editor, nid, content) {
    // content must be converted to anything that is dom-ish, the more I refine
    // this widget, the less I use jQuery into all of this, it justs brings an
    // awful lot of bugs.
    var element;
    if (content) {
      element = CKEDITOR.dom.element.createFromHtml('<div>' + content + '</div>');
    } else {
      element = new CKEDITOR.dom.element('span');
    }
    // @todo Here it might fail if the element is neither span or div... please fix this
    element.setAttribute('data-media-nid', nid);
    // Set not so stupid defaults
    element.setAttribute('data-media-width', '25%');
    element.setAttribute('data-media-float', 'left');
    return element;
  }

  /**
   * CKEditor selection is a nightmare, this saves us.
   *
   * @param CKEDITOR.editor editor
   *
   * @return CKEDITOR.dom.range
   *   May an instance, maybe not
   */
  function selectRangeForMediaInsertion(editor) {
    // Attempt to deterrmine if there is a selection or not
    var selection = editor.getSelection();
    var range;
    if (!selection || !selection.getRanges().length) {
      // Dawn, we're fucked, find another way around this.
      range = editor.createRange();
      range.moveToPosition(range.root, CKEDITOR.POSITION_BEFORE_END);
      editor.getSelection().selectRanges([range]);
    } else {
      range = selection.getRanges()[0];
    }
    if (!range.collapsed) {
      range.collapse();
    }
    range.moveToClosestEditablePosition();
    return range;
  }

  /**
   * Load nid content and set it into the editor as a widget
   *
   * @param CKEDITOR.editor editor
   * @param int nid
   */
  function createAndPlaceMedia(editor, nid, content) {
    // Fouque, why doesn't it work without this?
    editor.focus();

    var element = createMediaElement(editor, nid, content);

    // Nothing to say, I hate CKEditor
    var range = selectRangeForMediaInsertion(editor);
    if (!range) {
      return null;
    }

    editor.insertElement(element);

    // and finally, CKEditor is not that bad, once you read the doc
    // http://docs.ckeditor.com/#!/api/CKEDITOR.plugins.widget
    editor.widgets.initOn(element, 'ucmsmedia');
    // this call is not mandatory, but it forces a refresh, it allowed us to
    // detect bugs that were otherwise triggered on POST and almost invisible
    editor.updateElement();
  }

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
      // IMPORTANT: In order to know where the magic actually happen, have a see
      // into the ucms_contrib.js file, at cart initialization time.
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
          if (data && data.output && "string" === typeof data.output) {
            createAndPlaceMedia(editorEvent.editor, nid, data.output);
          } else {
            if (console) {
              parent.$.innerHTML = "Loading the element failed";
              console.log("error while rendering element, wrong content loaded from ajax");
            }
          }
        });
      }
    });
  }

  CKEDITOR.plugins.add('ucmsmediadnd', {
    requires: 'contextmenu,widget,ajax',

    init: function (editor) {

      // Attach external dnd listener
      editor.on('instanceReady', onInstanceReady);

      // Register our dialog
      CKEDITOR.dialog.add('ucmsMediaDialog', this.path + 'dialogs/media.js');

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
        template: '<div data-media-nid=""></div>',

        // Believe this or not, but we don't need any data, but if we don't set
        // it at some point, our element is empty, and has no data, it may break
        // CKE at some point, for an unknown and deep totally non-legit reason,
        // even if I could not reproduce the bug since, let's keep this
        init: function () {
          var nid = this.element.getAttribute('data-media-nid');
          if (nid) {
            fetchAndReplaceContent(editor, nid, this.element);
          }
          this.setData('nid', nid);
          this.setData('float', this.element.getAttribute('data-media-float'));
          this.setData('width', this.element.getAttribute('data-media-width'));
        },

        // Allow the editor to match our raw html as a widget instance
        // http://docs.ckeditor.com/#!/api/CKEDITOR.plugins.widget.definition-property-upcast
        upcast: function (element) {
          return !!element.attributes['data-media-nid'];
        },

        data: function () {
          // We never know, better do nothing than crash
          if (!this.data || !this.element || !this.element.$.parentElement) {
            return;
          }

          // Apply everything to the wrapper only
          // For backward compatibility, we also remove the "width" style.
          this.element.$.parentElement.style.width = '';
          if (this.data.width) {
            this.element.$.parentElement.style.maxWidth = this.data.width;
            this.element.$.setAttribute('data-media-width', this.data.width);
          } else {
            // We might want to set less aggressive defaults
            this.element.$.parentElement.style.maxWidth = '';
            this.element.$.setAttribute('data-media-width', '');
          }

          if (this.data.float) {
            this.element.$.parentElement.style.float = this.data.float;
            this.element.$.setAttribute('data-media-float', this.data.float);
          } else {
            this.element.$.parentElement.style.float = '';
            this.element.$.setAttribute('data-media-float', '');
          }
        }
      });

      // Since we can't inline our widget, we are going to provide a simple
      // helper for the users to be able to float left or right their widgets
      // http://docs.ckeditor.com/#!/guide/plugin_sdk_sample_2
      if (editor.contextMenu) {

        editor.addCommand('ucmsMediaDialog', new CKEDITOR.dialogCommand('ucmsMediaDialog'));

        editor.addMenuGroup('ucmsMediaGroup');
        editor.addMenuItem('ucmsMediaEdit', {
          label: 'Edit media display',
          icon: this.path + 'icons/left.png',
          command: 'ucmsMediaDialog',
          group: 'ucmsMediaGroup'
        });

        editor.contextMenu.addListener(function (element) {
          var widget = editor.widgets.getByElement(element);
          if (widget && "ucmsmedia" === widget.name) {
            return {
              ucmsMediaEdit: CKEDITOR.TRISTATE_OFF
            };
          }
        });
      }
    }
  });

}(CKEDITOR, Drupal, jQuery));
