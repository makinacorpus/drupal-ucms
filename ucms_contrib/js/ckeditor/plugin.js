/* jshint vars: true, forin: false, strict: true, browser: true,  jquery: true */
/* globals CKEDITOR, Drupal, jQuery */
(function (CKEDITOR, Drupal, $) {
  "use strict";

  function onInstanceReady(editorEvent) {

    // This should do the trick
    var parent = $(editorEvent.editor.ui.contentsElement.$).find('iframe').parent();

    try {
      $(parent).droppable('destroy');
    } catch (e) {
      // Might not be initialized yet
    }

    // Ok, we are going to be UGLYYYYYYYYYYYYYY.
    $(parent).droppable({
      //activeClass: "ckeditor-allow-drop",
      tolerance: "pointer",
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

  function createAndPlaceMedia (editor, nid, content) {

    var selection = editor.getSelection();
    var range = selection.getRanges()[0];
    var text = new CKEDITOR.dom.text(content);

    range.insertNode(text);
    range.selectNodeContents(text);

    var style = new CKEDITOR.style({
      element: 'div',
      attributes: {
        'class': 'media',
        'data-nid': nid
      }
    });

    style.type = CKEDITOR.STYLE_INLINE; // need to override... dunno why.
    style.applyToRange(range, editor);
    range.select();
  }

  // @see view-source:http://sdk.ckeditor.com/samples/draganddrop.html
  CKEDITOR.plugins.add('ucmsmediadnd', {
    requires: 'ajax,widget,clipboard',

    init: function (editor) {

      CKEDITOR.on('instanceReady', onInstanceReady);

//      CKEDITOR.on('drop', function (event) {
//        event.data.preventDefault(true);
//      });

//      editor.widgets.add('ucmsmedia', {
//        allowedContent: {},
//        pathName: 'media',
//
//        upcast: function(element) {
//          return (
//            element.name == 'div' &&
//            element.hasClass('media')
//          );
//        },
//
//        downcast: function(element) {
//          return CKEDITOR.htmlParser.fragment.fromHtml('{{12}}');
//        },
//
//        init: function() {
//          this.element.setHtml("<div class='media'>Test</div>");
//        }
//      });

      // This feature does not have a button, so it needs to be registered manually.
//      editor.addFeature(editor.widgets.registered.ucmsmedia);
//
//      editor.on('drop', function(event) {
//        CKEDITOR.plugins.clipboard.initDragDataTransfer(event);
//        var nid = event.data.dataTransfer.getData('nid');
//        console.log('Drop ' + nid);
//        // Use synchronous load, otherwise the drop event finishes before ajax loading.
//        var data = CKEDITOR.ajax.load('/node/' + nid + '/ajax');
//        // Set the drop value what we want it to be.
//        event.data.dataTransfer.setData('text/html', data);
//      });
    }
  });

//  var dragHandler = function(event) {
//    // Initialization of CKEditor data transfer facade is a necessary step to extend and unify native
//    // browser capabilities. For instance, Internet Explorer does not support any other data type than 'text' and 'URL'.
//    // Note: event is an instance of CKEDITOR.dom.event, not a native event.
//    CKEDITOR.plugins.clipboard.initDragDataTransfer(event);
//
//    event.data.dataTransfer.setData('nid', event.listenerData);
//
//    // Some text need to be set, otherwise drop event will not be fired.
//    event.data.dataTransfer.setData('text', 'x');
//    console.log('Drag ' + event.listenerData);
//  };

}(CKEDITOR, Drupal, jQuery));