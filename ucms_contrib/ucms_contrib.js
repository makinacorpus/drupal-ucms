(function ($) {
  Drupal.ucmsDraggableDefaults = {
    revert: true,
    opacity: 0.75,
    start: function () {
      // Show the regions that are empty
      $('.ucms-layout-empty-region').toggleClass('ucms-layout-empty-region ucms-layout-empty-region-hover');
      $('.ucms-layout-empty-block').toggleClass('ucms-layout-empty-block ucms-layout-empty-block-hover');
    },
    stop: function () {
      // TODO Don't hide region that are now not empty
      $('.ucms-layout-empty-region-hover').toggleClass('ucms-layout-empty-region ucms-layout-empty-region-hover');
      $('.ucms-layout-empty-block-hover').toggleClass('ucms-layout-empty-block ucms-layout-empty-block-hover');
    }
  };
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
        $ucmsCart.width() == 0 && $('#ucms-cart').show();
        $ucmsCart.animate({
          width: $ucmsCart.width() == 0 ? initial_width : '0px'
        }, function () {
          $ucmsCart.width() == 0 && $('#ucms-cart').hide();
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
              ui.draggable.animate(ui.draggable.data().uiDraggable.originalPosition);
            });
        }
      }));

      // Second drop zone, trash, accepting cart items or region items
      $('#ucms-cart-trash', context).droppable($.extend({}, Drupal.ucmsDroppableDefaults, {
        accept: "[data-nid].ucms-cart-item, [data-nid].ucms-region-item",
        drop: function (event, ui) {
          console.log('dropped', arguments);
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
            // remove form region
            $.post(settings.basePath + 'admin/ucms/layout/' + settings.ucmsLayout + '/remove', {
              'region': ui.draggable.parents('[data-region]').data('region')
            }).done(function () {
              ui.draggable.remove();
            }).fail(function () {

            });
          }
        }
      }));
      // Activate all draggables except sortables (aka region items)
      $('[data-nid]:not(.ucms-region-item)', context).draggable($.extend({}, Drupal.ucmsDraggableDefaults, {
        connectToSortable: '[data-region]',
        cursorAt: {top: 50, left: 50}
      }));
    }
  };

  /**
   * Behavior for handling cart drop-in and trashing items
   * @type {{attach: Drupal.behaviors.ucmsRegion.attach}}
   */
  Drupal.behaviors.ucmsRegion = {
    attach: function (context, settings) {
      // All regions are drop zones for cart items
      $('[data-region]', context).sortable({
        revert: true,
        items: '[data-nid]',
        connectWith: '[data-region], #ucms-cart-trash',
        opacity: 0.75,
        cursorAt: {top: 50, left: 50},
        tolerance: 'pointer',
        remove: function (event, ui) {
          console.log('remove', arguments);
        },
        change: function (event, ui) {
          console.log('change', arguments);
        },
        receive: function (event, ui) {
          console.log('receive', arguments);
          // Add the new element to the layout
          var $region = $(this);
          $.post(settings.basePath + 'admin/ucms/layout/' + settings.ucmsLayout + '/add', {
            region: $(this).data('region'),
            nid: ui.item.data('nid')
          }).done(function (data) {
            var elem = '<div class="ucms-region-item" data-nid="' + ui.item.data('nid') + '">' + data.node + '</div>';
            $region.find('.ui-sortable').append(elem);
            $('[data-region]', context).sortable("refresh");
          });
        },
        update: function (event, ui) {
          console.log('update', arguments);
        },
        stop: function (event, ui) {
          console.log('stop', arguments);
        }
      });
    }
  };
}(jQuery));
