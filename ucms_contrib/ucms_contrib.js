(function ($) {
  /**
   * Default settings for draggables
   * @type {{revert: boolean, opacity: number, helper: string, appendTo: string, containment: string, cursorAt: {top: number, left: number}}}
   */
  Drupal.ucmsDraggableDefaults = {
    revert: function (dropped) {
      // Revert only if not dropped anywhere
      return !dropped || !(dropped.hasClass('ui-sortable') || dropped.hasClass('ui-droppable'));
    },
    opacity: 0.75,
    helper: function () {
      // Remove bootstrap class as it messes up layout
      return $(this).clone().removeClass('col-md-6'); // current element is a draggable
    },
    appendTo: 'body',
    containment: 'document',
    cursorAt: {top: 50, left: 50}
  };

  /**
   * Default settings for droppables and sortables
   * @type {{tolerance: string, hoverClass: string, activate: Drupal.ucmsDroppableDefaults.activate, deactivate: Drupal.ucmsDroppableDefaults.deactivate}}
   */
  Drupal.ucmsDroppableDefaults = {
    tolerance: 'pointer',
    hoverClass: "drop-highlighted-hover",
    activate: function () {
      $(this).addClass('drop-highlighted');
    },
    deactivate: function () {
      $(this).removeClass('drop-highlighted');
    }
  };

  /**
   * Behavior for handling cart drop-in and trashing items
   * @type {{attach: Drupal.behaviors.ucmsCart.attach}}
   */
  Drupal.behaviors.ucmsCart = {
    attach: function (context, settings) {
      // Hide forms
      $('.ucms-contrib-result, .ucms-cart-item').find('form[id^=ucms-contrib-favorite-]').css('display', 'none');

      // Toggle cart
      var $ucmsCart = $('#ucms-contrib-cart');
      var initial_width = $ucmsCart.css('width');
      var $ucmsToggle = $('#ucms-cart-toggle');
      $ucmsToggle.click(function () {
        var shown = !$ucmsCart.css('margin-right') || $ucmsCart.css('margin-right') == '0px';
        $ucmsCart.animate({
          marginRight: shown ? '-' + initial_width : '0px'
        }, function () {
          $ucmsToggle.find('span').toggleClass('glyphicon-chevron-left glyphicon-chevron-right');
        });
      });

      // First drop zone, cart, accepting only admin items
      $('#ucms-cart', context).droppable($.extend({}, Drupal.ucmsDroppableDefaults, {
        accept: "[data-nid]:not(.ucms-cart-item):not(.ucms-region-item)",
        drop: function (event, ui) {
          var nid = ui.draggable.data('nid');
          $.get(settings.basePath + 'admin/cart/' + nid + '/add/nojs')
            .done(function (data) {
              // add to cart list
              var elem = '<div class="ucms-cart-item col-md-6" data-nid="' + nid + '">' + data.node + '</div>';
              $('#ucms-cart-list').append(elem).find('div:last-child').draggable(Drupal.ucmsDraggableDefaults);
            })
            .fail(function (xhr) {
              // display error and revert
              var err_elem = '<div class="error">' + xhr.responseJSON.error + '</div>';
              var $errElem = $('#ucms-cart-list').append(err_elem).find('div:last-child');
              setTimeout(function () {
                $errElem.fadeOut(function () {
                  $(this).remove();
                });
              }, 3000);
            });
        }
      }));

      // Second drop zone, trash, accepting cart items or region items
      $('#ucms-cart-trash', context).droppable($.extend({}, Drupal.ucmsDroppableDefaults, {
        accept: "[data-nid].ucms-cart-item, [data-nid].ucms-region-item",
        drop: function (event, ui) {
          ui.draggable.trashed = true;
          var nid = ui.draggable.data('nid');
          if (!ui.draggable.hasClass('ucms-region-item')) {
            // remove form cart
            $.get(settings.basePath + 'admin/cart/' + nid + '/remove/nojs')
              .done(function () {
                // remove from cart
                ui.draggable.remove();
              });
          }
          else {
            // Remove from region
            $.post(settings.basePath + 'admin/ucms/layout/' + settings.ucmsLayout.layoutId + '/remove', {
              region: ui.draggable.parents('[data-region]').data('region'),
              position: Math.max(ui.draggable.index() - 1, 0),
              token: settings.ucmsLayout.editToken
            }).done(function () {
              ui.draggable.remove();
            });
          }
        }
      }));

      // Activate all draggables except sortables (aka region items)
      $('[data-nid]:not(.ucms-region-item)', context).draggable($.extend({}, Drupal.ucmsDraggableDefaults, {
        connectToSortable: '[data-region]'
      }));
    }
  };

  /**
   * Behavior for handling cart drop-in and trashing items
   * @type {{attach: Drupal.behaviors.ucmsRegion.attach}}
   */
  Drupal.behaviors.ucmsRegion = {
    attach: function (context, settings) {
      if (!settings.ucmsLayout) return;
      var draggedItem;

      // All regions are drop zones for cart items
      $('[data-region]', context).sortable($.extend({}, Drupal.ucmsDroppableDefaults, Drupal.ucmsDraggableDefaults, {
        items: '[data-nid]',
        connectWith: '[data-region], #ucms-cart-trash',
        helper: null,
        placeholder: {
          element: function (element) {
            // Again create element without bootstrap classes as it messes layout
            var nodeName = element[0].nodeName.toLowerCase();
            return $(document.createElement(nodeName)).addClass('ui-sortable-placeholder');
          },
          update: function () {
          }
        },
        beforeStop: function (event, ui) {
          draggedItem = ui.item;
        },
        activate: function () {
          // We just been activated, show ourselves
          $(this).addClass('drop-highlighted')
        },
        deactivate: function () {
          // We just been deactivated, dim ourselves
          $(this).removeClass('drop-highlighted drop-highlighted-over');
          if (!$(this).find('[data-nid]').length) {
            $(this).parents('.ucms-layout-empty-region-hover').toggleClass('ucms-layout-empty-region ucms-layout-empty-region-hover');
            $(this).toggleClass('ucms-layout-empty-block ucms-layout-empty-block-hover');
          }
          else {
            $(this).parents('.ucms-layout-empty-region-hover').removeClass('ucms-layout-empty-region-hover');
            $(this).removeClass('ucms-layout-empty-block-hover');
          }
        },
        out: function () {
          // We are no longer on zone using a cart item
          $(this).removeClass('drop-highlighted-over');
        },
        receive: function (event, ui) {
          if (ui.item.trashed) {
            return; // Prevent receiving item on the way to the trash
          }
          var position = 0;
          if (ui.item.hasClass('ui-draggable')) {
            // coming from the cart
            position = $(this).data().uiSortable.currentItem.index();
          }
          else {
            position = ui.item.index();
          }

          // Add the new element to the layout
          $.post(settings.basePath + 'admin/ucms/layout/' + settings.ucmsLayout.layoutId + '/add', {
            region: $(this).data('region'),
            nid: ui.item.data('nid'),
            position: position, // Don't ask me why
            token: settings.ucmsLayout.editToken
          }).done(function (data) {
            var elem = '<div class="ucms-region-item" data-nid="' + ui.item.data('nid') + '">' + data.node + '</div>';
            $(draggedItem).replaceWith(elem);
          });
          // Remove from previous region if there is a sender
          if (ui.sender && ui.sender.data('region')) {
            $.post(settings.basePath + 'admin/ucms/layout/' + settings.ucmsLayout.layoutId + '/remove', {
              region: ui.sender.data('region'),
              token: settings.ucmsLayout.editToken
            });
          }
        },
        start: function (event, ui) {
          if (!ui.item.sender) {
            $(this).addClass('drop-highlighted-over');
          }
          ui.item.startPos = ui.item.index();
        },
        over: function () {
          $(this).addClass('drop-highlighted-over');
        },
        update: function (event, ui) {
          if (ui.item.trashed || this !== this.currentContainer || ui.item.hasClass('ui-draggable')) {
            // Prevent updating item on the way to the trash or if element was
            // dragged from another, it will be handled by received
            return;
          }
          // Add the new element to the layout
          $.post(settings.basePath + 'admin/ucms/layout/' + settings.ucmsLayout.layoutId + '/move', {
            region: $(this).data('region'),
            nid: ui.item.data('nid'),
            prevPosition: ui.item.startPos,
            position: ui.item.index(),
            token: settings.ucmsLayout.editToken
          });
        }
      }));

      // Add a custom dragging handler to activate empty region before activating sortables
      var wasDragging = false;
      $('[data-nid]', context)
        .mousemove(function () {
          if (wasDragging) {
            // Show the regions that are empty
            $('.ucms-layout-empty-region').toggleClass('ucms-layout-empty-region ucms-layout-empty-region-hover');
            $('.ucms-layout-empty-block').toggleClass('ucms-layout-empty-block ucms-layout-empty-block-hover');
            // refresh containment (window) size as our layout has now changed
            $('[data-nid]:not(.ucms-region-item)', context).draggable('refreshContainment');
          }
          wasDragging = false;
        })
        .mousedown(function () {
          wasDragging = true;
        })
        .mouseup(function () {
          wasDragging = false;
        });
    }
  };
}(jQuery));
