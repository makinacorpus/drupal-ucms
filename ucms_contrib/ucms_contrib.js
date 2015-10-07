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
      // First drop zone, cart
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
      // Second drop zone, trash
      $('#ucms-cart-trash', context).droppable($.extend({}, Drupal.ucmsDroppableDefaults, {
        accept: "[data-nid].ucms-cart-item, [data-nid].ucms-region-item",
        drop: function (event, ui) {
          var nid = ui.draggable.data('nid');
          if (!ui.draggable.hasClass('ucms-region-item')) {
            $.get(settings.basePath + 'admin/cart/' + nid + '/remove/nojs')
              .done(function () {
                // remove from cart
                ui.draggable.remove();
              });
          }
          else {
            $.post(settings.basePath + 'admin/ucms/layout/' + settings.ucmsLayout + '/remove', {
              'region': ui.draggable.parents('[data-region]').data('region')
            }).done(function () {
              ui.draggable.remove();
            }).fail(function () {

            });
          }
        }
      }));
      // Activate all draggables
      $('[data-nid]', context).draggable(Drupal.ucmsDraggableDefaults);
    }
  };

  /**
   * Behavior for handling cart drop-in and trashing items
   * @type {{attach: Drupal.behaviors.ucmsRegion.attach}}
   */
  Drupal.behaviors.ucmsRegion = {
    attach: function (context, settings) {
      // All region are drop zone for cart items
      $('[data-region]', context).droppable($.extend({}, Drupal.ucmsDroppableDefaults, {
        accept: "[data-nid].ucms-cart-item",
        drop: function (event, ui) {
          var $region = $(this);
          $.post(settings.basePath + 'admin/ucms/layout/' + settings.ucmsLayout + '/add', {
            'region': $(this).data('region'),
            'nid': ui.draggable.data('nid')
          }).done(function(data) {
            var elem = '<div class="ucms-region-item" data-nid="' + ui.draggable.data('nid') + '">' + data.node + '</div>';
            $region.append(elem);
          }).fail(function() {

          });
        }
      }));
    }
  };
}(jQuery));
