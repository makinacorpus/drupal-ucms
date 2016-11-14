(function ($) {
  Drupal.ucmsNewMenuItemCount = 0;

  Drupal.behaviors.ucmsTree = {
    attach: function (context, settings) {
      $('ol[data-menu]', context).once('ucms-tree', function () {
        var $menu = $(this);

        // Remove any empty element that was added by Drupal
        $menu.find('li:empty').remove();

        // We won't handle the trash on the page
        $('#ucms-cart-trash').hide();

        function updateHiddenField() {
          var toArray = $menu.nestedSortable('toArray', {startDepthCount: 0});
          // Add menu labels
          for (var i in toArray) {
            toArray[i].title = $('[data-mlid="' + toArray[i].id + '"] input').val();
          }
          $menu.closest('form').find('input[name=values]').val(JSON.stringify(toArray));
        }

        /**
         * Inputs
         */
        $menu.on('blur', 'div input', function () {
          updateHiddenField.call($(this).parents('[data-menu]'));
        });

        /**
         * Cart sortable
         */
        var listIndex = 0;
        $('#ucms-cart-list', context).sortable({
          // Connect with others lists and trash
          connectWith: '[data-can-receive]',
          items: '.ucms-cart-item',
          placeholder: 'ucms-placeholder', // Placeholder class = CSS background
          tolerance: 'pointer',
          toleranceElement: '> div',
          remove: function (event, ui) {
            var $cart = $('#ucms-cart-list');
            if (listIndex - 1 >= 0) {
              ui.item.clone().insertAfter($cart.children().get(listIndex - 1));
            }
            else {
              $cart.prepend(ui.item.clone());
            }
            $cart.sortable('refresh');
          },
          start: function(event, ui) {
            // Retain element position in cart when moving out
            listIndex = $(ui.item).index();
          }
        });

        /**
         * Tree sortable
         */
        $menu.filter('[data-can-receive]').nestedSortable({
          connectWith: '[data-menu][data-can-receive]',
          tabSize: 25,
          maxLevels: settings.ucmsTree.menuNestingLevel || 2,
          items: 'li',
          placeholder: 'ucms-placeholder', // Placeholder class = CSS background
          tolerance: "pointer",
          toleranceElement: '> div',
          attribute: 'data-mlid',
          excludeRoot: true,
          expression: /()([new_\d]+)/,
          receive: function (event, ui) {
            // Replace element coming from cart
            var elem = Drupal.theme('menuItem', ui.item);
            ui.item.replaceWith(elem);
            $(this).sortable('refresh');
            updateHiddenField.call(this);
          },
          update: updateHiddenField,
          create: updateHiddenField
        });

        /**
         * Close buttons
         */
        $menu.on('click', 'div span.glyphicon-remove', function () {
          var sortable = $(this).closest('[data-menu]');
          $(this).closest('li').remove();
          $(sortable).nestedSortable('refresh');
          updateHiddenField.apply(sortable);
        });

        $menu.find('li').each(function () {
          var $element = $(this);

          // Add elements at startup
          if ((!$element.parents('li').data('mlid') || $element.parents('li').data('mlid') > 0)) {
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

            $('<a class="add-here add-before use-ajax minidialog fade">Ajouter un contenu ici</a>')
              .wrapInner('<span class="btn btn-danger">')
              .attr('href', buildUrl.apply(this, [$element, false]))
              .appendTo($element.find('> div'));

            // Handle last elements, add a link after
            if ($element.is(':last-child') && $element.parent().data('menu')) {
              $('<a class="add-here add-after use-ajax minidialog fade">Ajouter un contenu ici</a>')
                .wrapInner('<span class="btn btn-danger">')
                .attr('href', buildUrl.apply(this, [$element, true]))
                .appendTo($element.find('> div'));
            }
          }
        });
        Drupal.attachBehaviors($menu);

        /**
         * Display node add buttons
         */
        var dragging = false;
        var currentItemIndex = null;

        $('body')
          .mousedown(function () {
            dragging = true;
          })
          .mouseup(function () {
            dragging = false;
          });

        $menu
          .mousemove(function (event) {
            if (dragging) {
              return;
            }

            var $menuItems = $menu.find('.tree-item');

            function toggleButtons(itemIndex) {
              if (typeof itemIndex === 'number') {
                $item = $menuItems.eq(itemIndex);
                $nextItem = $menuItems.eq(itemIndex + 1);

                $item.find('a.add-here').toggleClass('in');
                if ($nextItem) {
                  $nextItem.find('a.add-here').toggleClass('in');
                }
              }
            }

            $menuItems.each(function (index) {
              if (
                index !== currentItemIndex &&
                event.pageY >= $(this).offset().top &&
                event.pageY <= $(this).offset().top + $(this).outerHeight()
              ) {
                toggleButtons(currentItemIndex);
                toggleButtons(index);
                currentItemIndex = index;
                return false; // Stops the loop.
              }
            });
          })
          .on('mouseenter mouseup', function (event) {
            if (event.type === 'mouseup') {
              dragging = false;
            }
            $menu.triggerHandler($.Event('mousemove', {
              pageX: event.pageX,
              pageY: event.pageY
            }));
          })
          .on('mouseleave mousedown', function (event) {
            // Hides buttons if the cursor leaves the tree region or the user
            // press mouse's button (maybe to drag).
            if (event.type === 'mousedown' && $(event.target).hasClass('btn')) {
              return;
            }
            $menu.find('a.add-here').removeClass('in');
            currentItemIndex = null;
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
