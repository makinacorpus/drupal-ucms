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
            element.name == 'div' &&
            element.hasClass('ucms-cart-item') &&
            settings.mediaBundles.indexOf(element.data('bundle')) > -1
          );
        }
      });

      // This feature does not have a button, so it needs to be registered manually.
      editor.addFeature(editor.widgets.registered.ucmsmedia);

      editor.on('paste', function(event) {
        console.log('Paste!');
        var nid = event.data.dataTransfer.getData('nid');

        CKEDITOR.ajax.load('/node/' + nid + '/ajax', function(data) {
          event.data.dataValue = data;
        });
      });
    }
  });

  var dragHandler = function(event) {
    console.log('Drag!');
    var target = event.data.getTarget();

    // Initialization of CKEditor data transfer facade is a necessary step to extend and unify native
    // browser capabilities. For instance, Internet Explorer does not support any other data type than 'text' and 'URL'.
    // Note: evt is an instance of CKEDITOR.dom.event, not a native event.
    CKEDITOR.plugins.clipboard.initDragDataTransfer(event);

    event.data.dataTransfer.setData('nid', target.data('nid'));
    event.data.dataTransfer.setData('bundle', target.data('bundle'));

    // We need to set some normal data types to backup values for two reasons:
    // * In some browsers this is necessary to enable drag and drop into text in editor.
    // * The content may be dropped in another place than the editor.
    event.data.dataTransfer.setData('text/html', target.getText());
  };

  var medias = CKEDITOR.document.getById('ucms-cart-list').find('.ucms-cart-item');
  for (var i = 0; i < medias.count(); i++) {
    //console.log(medias.getItem(i));
    //medias.getItem(i).removeListener('dragstart', dragHandler);
    medias.getItem(i).on('dragstart', dragHandler);
  }

  //CKEDITOR.document.getById('ucms-cart-list').on('dragstart', dragHandler);

}(CKEDITOR, Drupal));
