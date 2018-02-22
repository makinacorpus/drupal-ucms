/* jshint forin: false, strict: true, browser: true,  jquery: true */
/* globals CKEDITOR, Drupal */
(function (CKEDITOR, Drupal, $) {
  "use strict";

  CKEDITOR.plugins.add('ucmsembed', {
    requires: 'widget',
    icons: 'ucmsembed',

    init: function (editor) {

      // Register our dialog
      editor.addCommand('ucmsembed', new CKEDITOR.dialogCommand('ucmsEmbedDialog'));
      editor.ui.addButton('ucmsembed', {label: Drupal.t("Copy/paste IFRAME code"), command: 'ucmsembed'});

      // For later, anyone trying to change or fix this code, please first read:
      // http://docs.ckeditor.com/#!/guide/widget_sdk_tutorial_1
      editor.widgets.add('ucmsembedwidget', {

        inline: false,
        draggable: true,

        // allowedContent: 'div(!ucms-embed); iframe(*)[*]{*}',
        // VERY IMPORTANT: this needs the global editor ACF configuration to
        // allow arbitrary div/iframe elements!
        allowedContent: '*[*]{*}(*)',
        requiredContent: 'div(ucms-embed)',
        template: '<div class="ucms-embed"><iframe width="100%" height="50px"></iframe></div>',

        // Allow the editor to match our raw html as a widget instance
        // http://origin-docs.ckeditor.com/ckeditor4/docs/#!/api/CKEDITOR.plugins.widget.definition-property-upcast
        upcast: function (element) {
          return element.name === 'div' && element.hasClass('ucms-embed');
        },
      });

      CKEDITOR.dialog.add('ucmsEmbedDialog', function (editor) {
        return {
          title: Drupal.t("Embed"),
          minWidth: 400,
          minHeight: 30,
          contents: [{
            id: 'tab-content',
            label: Drupal.t('Embed'),
            elements: [{
              type: 'textarea',
              id: 'iframe',
              style: 'min-height: 50px !important;',
              expand: true,
              label: Drupal.t('Copy/paste IFRAME code'),
              'default': '',
              setup: function (element) {
                element.setAttribute("style", "height: auto !important;");
              }
            }]
          }],
          onOk: function () {
            var element = editor.document.createElement('div', {attributes: {"class": "ucms-embed"}});
            element.setHtml(this.getContentElement('tab-content', 'iframe').getValue());
            editor.insertElement(element);
            editor.widgets.initOn(element, 'ucmsembedwidget');
          }
        };
      });
    }
  });

}(CKEDITOR, Drupal, jQuery));
