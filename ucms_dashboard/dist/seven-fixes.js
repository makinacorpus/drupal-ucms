// @todo rewrite me without jQuery, with typescript
(function($, Drupal, document) {
  "use strict";

  var dropdownMenus = [];

  /**
   * Drupal behavior, find pages, spawn them, attach their behaviours.
   */
  Drupal.behaviors.sevenFixes = {
    attach: function(context, settings) {

      var opened = [];

      // Emulates bootstrap dropdowns.
      $(context).find('.dropdown-toggle').once('dropdown-toggle', function () {
        var link = $(this);
        var parent = link.parent();
        var child = parent.find('> .dropdown-menu');
        if (child.length) {
          child.hide();
          dropdownMenus.push(parent);
          link.click(function () {
            child.show();
          });
        }
      });

      // Close dropdowns handler
      $(document).click(function(event) {
        dropdownMenus.forEach(function (element) {
          if (!$.contains(element.get(0), event.target)) {
            element.find('> .dropdown-menu').hide();
          }
        });
      })
    }
  };

}(jQuery, Drupal, document));
