(function ($) {
  Drupal.ucmsNewMenuItemCount = 0;

  Drupal.behaviors.ucmsTree = {
    attach: function (context) {
      // Remove any empty element that was added by Drupal
      $('ol[data-menu] li:empty', context).remove();

      function updateHiddenField() {
        var toArray = $(this).nestedSortable('toArray', {startDepthCount: 0});
        $('[name*=' + $(this).data('menu') + ']').val(JSON.stringify(toArray));
      }

      /**
       * Tree sortable
       */
      $('[data-menu][data-can-receive]', context).nestedSortable($.extend({}, Drupal.ucmsSortableDefaults, {
        connectWith: '[data-menu][data-can-receive]',
        tabSize: 25,
        maxLevels: 2,
        isTree: true,
        expandOnHover: 700,
        startCollapsed: false,
        items: 'li',
        toleranceElement: '> div',
        attribute: 'data-mlid',
        excludeRoot: true,
        expression: /()([new_\d]+)/,
        receive: function (event, ui) {
          // Only replace element if coming from cart
          if ($(ui.item).closest('#ucms-cart-list').length) {
            var elem = Drupal.theme('menuItem', ui.item);
            var olderBrother = $(this).find("> *:nth-child(" + (Drupal.ucmsTempReceivedPos + 1) + ")");
            if (olderBrother.length) {
              olderBrother.before(elem);
            } else {
              $(this).append(elem);
            }
            $(this).sortable('refresh');
          }
          updateHiddenField.call(this);
        },
        update: updateHiddenField,
        create: updateHiddenField
      }));

      $('[data-menu] div span').click(function () {
        var sortable = $(this).closest('[data-menu]');
        $(this).closest('li').remove();
        $(sortable).nestedSortable('refresh');
        updateHiddenField.apply(sortable);
      });
    }
  };

  Drupal.theme.prototype.menuItem = function (elem) {
    // First find the nid of the element.
    var nid = $(elem).data('nid'),
      h2 = $(elem).find('h2').first().text();
    return '<li data-name="' + nid + '" data-mlid="new_' + Drupal.ucmsNewMenuItemCount++ + '">' +
      '<div class="tree-item">' + h2 + '<span class="glyphicon glyphicon-remove"></span></div></li>';
  };
}(jQuery));
