(function ($) {
  Drupal.ucmsNewMenuItemCount = 0;

  Drupal.behaviors.ucmsTree = {
    attach: function (context) {
      // Remove any empty element that was added by Drupal
      $('ol[data-menu] li:empty', context).remove();

      function updateHiddenField() {
        var toArray = $(this).nestedSortable('toArray', { startDepthCount: 0 });
        // Add menu labels
        for (var i in toArray) {
          toArray[i].title = $('[data-mlid="' + toArray[i].id + '"] input').val();
        }
        $('[name*="' + $(this).data('menu') + '"]').val(JSON.stringify(toArray));
      }

      /**
       * Inputs
       */
      $('[data-menu] input', context).on('blur', function() {
        updateHiddenField.call($(this).parents('[data-menu]'));
      });

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
        forcePlaceholderSize: false,
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

      /**
       * Close buttons
       */
      $('[data-menu] div span.glyphicon-remove').click(function () {
        var sortable = $(this).closest('[data-menu]');
        $(this).closest('li').remove();
        $(sortable).nestedSortable('refresh');
        updateHiddenField.apply(sortable);
      });

      /**
       * Display node add buttons
       */
      var dragging = false;
      $('body').mousedown(function () {
        dragging = true;
      }).mouseup(function () {
        dragging = false;
      }).mousemove(function (event) {
        if (dragging) {
          return;
        }
        function isNear($element, distance, event) {
          var left = $element.offset().left - distance,
            top = $element.offset().top - distance,
            right = left + $element.outerWidth() + ( 2 * distance ),
            bottom = top + $element.outerHeight() + ( 2 * distance ),
            x = event.pageX,
            y = event.pageY;
          return ( x > left && x < right && y > top && y < bottom );
        }

        $('ol[data-menu] li').each(function () {
          var $element = $(this);

          // If mouse is around element by 18 pixels
          if (isNear($element, 18, event)) {

            // Only add element if not already present and parent is saved
            if (
              !$element.find('a.add-here').length &&
              ($element.data('mlid') > 0) &&
              (!$element.parents('li').data('mlid') || $element.parents('li').data('mlid') > 0)
            ) {

              var buildUrl = function ($li, isAfter) {
                var params = {
                  position: isAfter ? $li.index() + 1 : $li.index(),
                  menu: $element.parents('[data-menu]').data('menu'),
                  parent: $element.parents('li').data('mlid'),
                  destination: 'admin/dashboard/tree',
                  minidialog: 1
                };
                return '/node/add/here?' + jQuery.param(params);
              };
              $('<a class="add-here add-before use-ajax minidialog">Ajouter un contenu ici</a>')
                .wrapInner('<span class="btn btn-danger">')
                .attr('href', buildUrl.apply(this, [$element, false]))
                .appendTo($element.find('> div'));

              // Handle last elements, add a link after
              if ($element.is(':last-child') && $element.parent().data('menu')) {
                $('<a class="add-here add-after use-ajax minidialog">Ajouter un contenu ici</a>')
                  .wrapInner('<span class="btn btn-danger">')
                  .attr('href', buildUrl.apply(this, [$element, true]))
                  .appendTo($element.find('> div'));
              }
              Drupal.attachBehaviors($element);
            }
          } else {
            $element.find('a.add-here').remove();
          }
        });
      });


    }
  };

  Drupal.theme.prototype.menuItem = function (elem) {
    // First find the nid of the element.
    var nid = $(elem).data('nid');
    var h2 = $(elem).find('h2 a').first().html();

    return '<li data-name="' + nid + '" data-mlid="new_' + (Drupal.ucmsNewMenuItemCount++) + '">' +
        '<div class="tree-item clearfix">' +
          '<input type="text" class="form-control form-text" value="' + h2 + '"/>' +
          '<span class="glyphicon glyphicon-remove"></span>' +
        '</div>' +
      '</li>';
  };

}(jQuery));
