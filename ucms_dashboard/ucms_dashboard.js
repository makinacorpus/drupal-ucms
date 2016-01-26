(function ($) {
  /**
   * Behavior for handling contextual pane generic stuff
   * @type {{attach: Drupal.behaviors.ucmsDashboardPane.attach}}
   */
  Drupal.behaviors.ucmsDashboardPane = {
    attach: function (context, settings) {
      // Toggle cart
      var $contextualPane = $('#contextual-pane');
      var initial_width = $contextualPane.css('width');
      var $toggle = $('#contextual-pane-toggle');
      $toggle.click(function () {
        var shown = !$contextualPane.css('margin-right') || $contextualPane.css('margin-right') == '0px';
        $contextualPane.animate({
          marginRight: shown ? '-' + initial_width : '0px'
        }, function () {
          $toggle.find('span').toggleClass('glyphicon-chevron-left glyphicon-chevron-right');
        });
      });
    }
  };
}(jQuery));
