(function ($) {
  /**
   * Behavior for handling contextual pane and its tabs
   * @type {{attach: Drupal.behaviors.ucmsDashboardPane.attach}}
   */
  Drupal.behaviors.ucmsDashboardPane = {
    attach: function (context, settings) {
      $(context).find('#contextual-pane').once('ucmsDashboardPane', function(){
        var $contextualPane = $('#contextual-pane', context);
        var $toggle = $('#contextual-pane-toggle', context);

        // Handle pane position
        var $panePositionSwitch = $('#contextual-pane-switch-position');
        $panePositionSwitch.click(function () {
          $contextualPane.toggleClass('pane-right pane-down');
          position = panePosition()
          $.cookie('contextual-pane-position', position, {path: '/'});
          $panePositionSwitch.find('span').toggleClass('glyphicon-collapse-down glyphicon-expand');
          resizeTabs();
        });
        if ($.cookie('contextual-pane-position') && $.cookie('contextual-pane-position') !== 'right') {
          // Second toggle if pane must be hidden
          $panePositionSwitch.click();
        }

        var position = panePosition();
        var initial_size = position == 'right' ? $contextualPane.css('width') : $contextualPane.css('height');

        /**
         * Quick function to determine pane position.
         * @returns {boolean}
         */
        function panePosition() {
          if ($contextualPane.hasClass('pane-right')) {
              return 'right';
          } else if ($contextualPane.hasClass('pane-down')) {
              return 'down';
          } else if ($contextualPane.hasClass('pane-left')) {
              return 'left';
          } else if ($contextualPane.hasClass('pane-up')) {
              return 'up';
          }
        }

        /**
         * Quick function to determine if pane is hidden.
         * @returns {boolean}
         */
        function paneIsHidden() {
          var propName = (position == 'right' ? 'margin-right' : 'margin-bottom');
          return $contextualPane.css(propName) && $contextualPane.css(propName) != '0px';
        }

        /**
         * Hide or show pane, and toggle link
         */
        function togglePane(shown) {
          $.cookie('contextual-pane-hidden', !shown, {path: '/'});
          var prop = {};
          prop[position == 'right' ? 'marginRight' : 'marginBottom'] = shown ? '0px' : '-' + initial_size;
          $contextualPane.animate(prop);
        }

        // Action to do on button click
        $toggle_link = $toggle.find('a');
        $toggle_link.click(function () {
          var $currentLink = $(this);

          // Hide whole pane if current active link is clicked
          if ($currentLink.hasClass('active')) {
            togglePane(false);
            // Update class
            $toggle_link.removeClass('active');
          }
          else {
            // If the pane is hidden, open it
            if (paneIsHidden()) {
              togglePane(true);
            }
            // Change tab status
            $contextualPane.find('.tabs > div').removeClass('active');
            $contextualPane.find('div[id=' + $currentLink.attr('href').substr(1) + ']').addClass('active');

            // Update link's class
            $toggle_link.removeClass('active');
            $currentLink.addClass('active');
          }
        });

        // Initial toggle for default tab
        $toggle.find('a[href=#tab-' + settings.ucms_dashboard.defaultPane + ']').click();
        if ($.cookie('contextual-pane-hidden') && $.cookie('contextual-pane-hidden') !== 'false') {
          // Second toggle if pane must be hidden
          $toggle.find('a[href=#tab-' + settings.ucms_dashboard.defaultPane + ']').click();
        }

        // Handle tab height
        function resizeTabs() {
          $contextualPane.find('.tabs').height($contextualPane.find('.inner').height() - $contextualPane.find('.actions').height());
        }

        resizeTabs();
      });
    }
  };

  /**
   * Behavior for handling contextual pane actions
   * @type {{attach: Drupal.behaviors.ucmsDashboardPane.attach}}
   */
  Drupal.behaviors.ucmsDashboardPaneActions = {
    attach: function (context) {
      $(context).find('#page').once('ucmsDashboardPaneActions', function(){
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
          $contextualPane.find('.inner .actions').append($clonedButton);
        });
      });
    },
    detach: function (context) {
      // Destroy all previous buttons
      if ($(context).find('#page').length) {
        var $contextualPane = $('#contextual-pane');
        $contextualPane.find('.actions').find('input[type=submit], button, a.btn').remove();
        $(context).find('#page').removeClass('ucmsDashboardPaneActions-processed');
      }
    }
  };
}(jQuery));
