/* jshint vars: true, forin: false, strict: true, browser: true,  jquery: true */
/* globals CKEDITOR, Drupal, jQuery */
(function (CKEDITOR, Drupal) {
  "use strict";

  CKEDITOR.plugins.add('ucmsmediadnd', {
    requires: 'ajax,widget,clipboard',

    init: function(editor) {
      console.log('Init!');
      var settings = Drupal.settings.ucms_contrib;

      editor.widgets.add('ucmsmedia', {
        allowedContent: true,
        //allowedContent: 'div(!node)[*]',
        //requiredContent: '',
        pathName: 'media',

        upcast: function(element) {
          return (
            element.name == 'span' &&
            element.hasClass('ucmsdnd')
            //&& settings.mediaBundles.indexOf(element.data('bundle')) > -1
          );
        }
      });

      // This feature does not have a button, so it needs to be registered manually.
      editor.addFeature(editor.widgets.registered.ucmsmedia);

      editor.on('drop', function(event) {
        console.log('Drop!');
        CKEDITOR.plugins.clipboard.initDragDataTransfer(event);
        var nid = event.data.dataTransfer.getData('nid');
        // Set the drop value what we want it to be
        event.data.dataTransfer.setData('text/html', '<span class="ucmsdnd">' + nid + '</span>');
        /*
        CKEDITOR.ajax.load('/node/' + nid + '/ajax', function(data) {
          event.data.dataValue = data;
        });
        */
      });
    }
  });

  var dragHandler = function(event) {
    console.log('Drag!');

    // Initialization of CKEditor data transfer facade is a necessary step to extend and unify native
    // browser capabilities. For instance, Internet Explorer does not support any other data type than 'text' and 'URL'.
    // Note: event is an instance of CKEDITOR.dom.event, not a native event.
    CKEDITOR.plugins.clipboard.initDragDataTransfer(event);

    event.data.dataTransfer.setData('nid', event.listenerData);

    // Some text need to be set, otherwise drop event will not be fired.
    event.data.dataTransfer.setData('text', 'x');
  };

  var medias = CKEDITOR.document.getById('ucms-cart-list').find('.ucms-cart-item');
  for (var i = 0; i < medias.count(); i++) {
    var element = medias.getItem(i);
    var nid = element.$.dataset.nid;
    element.on('dragstart', dragHandler, null, nid);
  }

}(CKEDITOR, Drupal));