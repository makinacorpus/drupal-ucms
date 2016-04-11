(function ($) {
  /**
   * Some general behaviors
   */
  Drupal.behaviors.ucmsDashboard = {
    attach: function (context) {
      // Prevent chrome bug with inputs inside anchors
      $('#ucms-contrib-facets', context).find('a input').click(function () {
        location.href = $(this).parents('a').attr('href');
      });

    }
  };

  /**
   * Behavior for handling contextual pane and its tabs
   * @type {{attach: Drupal.behaviors.ucmsDashboardPane.attach}}
   */
  Drupal.behaviors.ucmsDashboardPane = {
    /**
     * Handle tab height
     */
    resizeTabs: function () {
      var $contextualPane = $('#contextual-pane');
      $contextualPane.find('.tabs').height($contextualPane.find('.inner').height() - $contextualPane.find('.actions').height());
    },
    attach: function (context, settings) {
      $(context).find('#contextual-pane').once('ucmsDashboardPane', function () {
        var $contextualPane = $('#contextual-pane', context);
        var $toggle = $('#contextual-pane-toggle', context);
        var $page = $('#page', context);


        // Handle pane position
        var $panePositionSwitch = $('#contextual-pane-switch-position');
        $panePositionSwitch.click(function () {
          $contextualPane.toggleClass('pane-right pane-down');
          position = panePosition();
          $.cookie('contextual-pane-position', position, {path: '/'});
          $panePositionSwitch.find('span').toggleClass('glyphicon-collapse-down glyphicon-expand');
          Drupal.behaviors.ucmsDashboardPane.resizeTabs();
          $page.css('padding-right', position === 'right' ? initial_size : '15ppx');
        });
        if ($.cookie('contextual-pane-position') && $.cookie('contextual-pane-position') !== 'right') {
          // Second toggle if pane must be hidden
          $panePositionSwitch.click();
        }

        var position = panePosition();
        var initial_size = position === 'right' ? $contextualPane.css('width') : $contextualPane.css('height');
        if (position === 'right') {
          $page.css('padding-right', initial_size);
        }

        /**
         * Quick function to determine pane position.
         * @returns {string}
         */
        function panePosition() {
          var positions = ['right', 'down', 'left', 'up'];
          for (var x in positions) {
            if ($contextualPane.hasClass('pane-' + positions[x])) {
              return positions[x];
            }
          }
        }

        /**
         * Quick function to determine if pane is hidden.
         * @returns {boolean}
         */
        function paneIsHidden() {
          var propName = (position === 'right' ? 'margin-right' : 'margin-bottom');
          return $contextualPane.css(propName) && $contextualPane.css(propName) !== '0px';
        }

        /**
         * Hide or show pane, and toggle link
         */
        function togglePane(shown, fast) {
          $.cookie('contextual-pane-hidden', !shown, {path: '/'});
          var prop = {};
          prop[position === 'right' ? 'marginRight' : 'marginBottom'] = shown ? '0px' : '-' + initial_size;
          if (fast) {
            $contextualPane.css(prop);
            if (position === 'right') {
              $page.css('padding-right', shown ? initial_size : '15px');
            }
          }
          else {
            $contextualPane.animate(prop);
            if (position === 'right') {
              $page.animate({paddingRight: shown ? initial_size : '15px'});
            }
          }
        }

        // Action to do on button click
        var $toggle_link = $toggle.find('a');
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
            Drupal.behaviors.ucmsDashboardPane.resizeTabs()
          }
          return false; // Prevent hash change
        });

        // Initial toggle for default tab
        if ($.cookie('contextual-pane-hidden') && $.cookie('contextual-pane-hidden') !== 'false') {
          // Pane must be hidden
          togglePane(false, true);
          $toggle_link.removeClass('active');
        }
        else {
          $toggle.find('a[href=#tab-' + settings.ucms_dashboard.defaultPane + ']').click();
        }

        $(window).resize(Drupal.behaviors.ucmsDashboardPane.resizeTabs);
      });
    }
  };

  /**
   * Behavior for handling contextual pane actions
   * @type {{attach: Drupal.behaviors.ucmsDashboardPane.attach}}
   */
  Drupal.behaviors.ucmsDashboardPaneActions = {
    attach: function (context) {
      $(context).find('#page').once('ucmsDashboardPaneActions', function () {
        var $contextualPane = $('#contextual-pane');
        // Get all buttons (link or input) in form-actions
        var $buttons = $('#page .form-actions', context).children('.btn-group, input[type=submit], button, a.btn');
        // Iterate in reverse as they are floated right
        $($buttons.get().reverse()).each(function () {

          $(this).find('input[type=submit], button, a.btn')
            .add($(this).filter('input[type=submit], button, a.btn'))
            .each(function () {
              // Do not hack click if there are events
              if (!$.isEmptyObject($(this).data()) || $(this).is('a')) {
                return;
              }

              // Catch click event and delegate to original
              var originalElem = this;
              $(this).click(function (evt) {
                console.log('old', originalElem);
                console.log('clicked', evt);
                // Simulate click on original element
                if (originalElem !== evt.currentTarget) {
                  $(originalElem).click();
                  return false;
                }
              });
            });

          var $clonedElement = $(this).clone(true);
          $contextualPane.find('.inner .actions').append($clonedElement);
        });
        Drupal.behaviors.ucmsDashboardPane.resizeTabs();
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
