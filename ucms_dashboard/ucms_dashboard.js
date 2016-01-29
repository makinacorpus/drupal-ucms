(function ($) {
  /**
   * Behavior for handling contextual pane generic stuff
   * @type {{attach: Drupal.behaviors.ucmsDashboardPane.attach}}
   */
  Drupal.behaviors.ucmsDashboardPane = {
    attach: function (context, settings) {
      // Toggle cart
      var $contextualPane = $('#contextual-pane', context);
      var initial_width = $contextualPane.css('width');
      var $toggle = $('#contextual-pane-toggle', context);
      $toggle.click(function () {
        var hidden = !$contextualPane.css('margin-right') || $contextualPane.css('margin-right') == '0px';
        $.cookie('contextual-pane-hidden', hidden, {path: '/'});
        $contextualPane.animate({
          marginRight: hidden ? '-' + initial_width : '0px'
        }, function () {
          $toggle.find('span').toggleClass('glyphicon-chevron-left glyphicon-chevron-right');
        });
      });
      // Initial toggle if needed
      if ($.cookie('contextual-pane-hidden') && $.cookie('contextual-pane-hidden') !== 'false') {
        $contextualPane.css('margin-right', '-' + initial_width);
        $toggle.find('span').toggleClass('glyphicon-chevron-left glyphicon-chevron-right');
      }
    }
  };

  /**
   * Behavior for handling contextual pane actions
   * @type {{attach: Drupal.behaviors.ucmsDashboardPane.attach}}
   */
  Drupal.behaviors.ucmsDashboardPaneActions = {
    attach: function (context) {
      if ($(context).find('#page').length) {
        var $contextualPane = $('#contextual-pane');
        // Get all buttons (link or input) in form-actions
        var $buttons = $('#page .form-actions', context).find('input[type=submit], button, a.btn');
        // Iterate in reverse as they are floated right
        $($buttons.get().reverse()).each(function () {
          var $originalButton = $(this);
          var $clonedButton = $originalButton.clone().click(function () {
            // Simulate click on original button
            if (!$(this).is('a')) {
              $originalButton.click();
            }
          });
          $contextualPane.find('.inner').append($clonedButton)
        });
      }
    },
    detach: function (context) {
      // Destroy all previous buttons
      if ($(context).find('#page').length) {
        var $contextualPane = $('#contextual-pane');
        $contextualPane.find('.actions').find('input[type=submit], button, a.btn').remove()
      }
    }
  };
}(jQuery));
