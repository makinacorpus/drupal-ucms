/* jshint vars: true, forin: false, strict: true, browser: true,  jquery: true */
/* globals CKEDITOR Drupal */
(function (CKEDITOR, Drupal) {
  "use strict";

  /**
   * Get the selected media widget, first element inside the wrapper.
   *
   * @param CKEDITOR.editor editor
   *
   * @return CKEDITOR.widget
   */
  function getSelectedMediaWidget(editor) {
    var element = editor.getSelection().getStartElement();
    if (element) {
      var widget = editor.widgets.getByElement(element);
      if (widget) {
        return widget;
      }
    }
  }

  // @todo this should be fixed using
  // http://docs.ckeditor.com/?_escaped_fragment_=/guide/widget_sdk_tutorial_2#!/guide/widget_sdk_tutorial_2
  CKEDITOR.dialog.add('ucmsMediaDialog', function (editor) {

    return {
      title: Drupal.t("Media properties"),
      minWidth: 400,
      minHeight: 200,
      contents: [
        {
          id: 'tab-content',
          label: Drupal.t('Properties'),
          elements: [
            {
              type: 'select',
              id: 'float',
              label: Drupal.t('Position'),
              'default': '',
              items: [
                [ Drupal.t("Normal"), '' ],
                [ Drupal.t("Left of text"), 'left' ],
                [ Drupal.t("Right of text"), 'right' ]
              ]
            },
            {
              type: 'select',
              id: 'width',
              label: Drupal.t('Width (remains at 100% if not left or right)'),
              'default': '100%',
              items: [
                [ Drupal.t("100% (default)"), '100%' ],
                [ Drupal.t("75%"), '75%' ],
                [ Drupal.t("50%"), '50%' ],
                [ Drupal.t("25%"), '25%' ]
              ]
            }
          ]
        }
      ],

      onShow: function () {

        var editor = this.getParentEditor();
        var widget = getSelectedMediaWidget(editor);

        if (!widget) {
          return; // this in most cases will not happen
        }

        // Fill in values from the current selection
        var widthElement = this.getContentElement('tab-content', 'width');
        var floatElement = this.getContentElement('tab-content', 'float');
        widthElement.setValue(widget.data.width);
        floatElement.setValue(widget.data.float);
      },

      // This mostly duplicates code from the 'link' plugin.
      onOk: function () {

        var editor = this.getParentEditor();
        var widget = getSelectedMediaWidget(editor);

        if (!widget) {
          return; // this in most cases will not happen
        }

        var data = {};

        this.commitContent(data);

        var widthElement = this.getContentElement('tab-content', 'width');
        var floatElement = this.getContentElement('tab-content', 'float');
        widget.setData('width', widthElement.getValue());
        widget.setData('float', floatElement.getValue());
      }
    };
  });

}(CKEDITOR, Drupal));
