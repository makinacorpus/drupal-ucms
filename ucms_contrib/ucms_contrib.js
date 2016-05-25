(function ($) {
  // This variable is used because of this bug https://bugs.jqueryui.com/ticket/4303
  // so as workaround, we save the helper and its position before it's removed
  Drupal.ucmsTempReceivedPos = 0;
  Drupal.ucmsIsSameContainer = false;

  var UcmsCart;

  $.fn.extend({
    /**
     * Used through a Drupal ajax command
     */
    UcmsCartAdd: function (data) {
      if (data.node) {
        // Add item to list
        var elem = '<div class="ucms-cart-item col-md-6" data-nid="' + data.nid + '">' + data.node + '</div>';
        $(elem).appendTo('#ucms-cart-list');
        $(UcmsCart).sortable("refresh");
      }
    },
    UcmsCartRefresh: function (data) {
      if (data) {
        $('#ucms-contrib-cart').replaceWith(data);
        Drupal.attachBehaviors($('#ucms-contrib-cart'));
      }
    },
    UcmsCartRemove: function (data) {
      console.log("do me like one of your french girls!");
    }
  });

  /**
   * Default settings for droppables and sortables
   */
  Drupal.ucmsSortableDefaults = {
    // This allows any size of element, it will the mouse pointer that is taken
    // into account, normally...
    tolerance: 'pointer',

    placeholder: 'ucms-placeholder', // Placeholder class = CSS background
    forcePlaceholderSize: true,
    opacity: 0.75,

    // Some classes for activation
    activate: function () {
      $(this).addClass('ucms-highlighted');
    },
    deactivate: function () {
      $(this).removeClass('ucms-highlighted');
    },

    // May solve scrolling issues, try "parent", "document", "window".
    containment: document.body
  };

  /**
   * Behavior for handling cart drop-in and trashing items
   */
  Drupal.behaviors.ucmsCart = {
    attach: function (context, settings) {
      /**
       * Admin items: can be dropped on cart, always reverted
       */
      $('.ucms-contrib-result', context).parent().sortable($.extend({}, Drupal.ucmsSortableDefaults, {
        // Connect with cart
        connectWith: '#ucms-cart-list',

        update: function () {
          // Cancel any sort as there is no point to retain positions
          $(this).sortable('cancel');
        }
      }));

      /**
       * Cart: accepting admin items, can be removed.
       */
      $('#ucms-cart-list', context).sortable($.extend({}, Drupal.ucmsSortableDefaults, {

        // Connect with others lists and trash
        connectWith: '[data-can-receive], #ucms-cart-trash',

        beforeStop: function (event, ui) {
          Drupal.ucmsTempReceivedPos = ui.helper.index();
          // Need to save the fact that we are receiving or not for update()
          Drupal.ucmsIsSameContainer = !!$(ui.placeholder).closest(this).length;
        },

        // Ce hack de batard!
        start: function (event, ui) {
          $('iframe').hide();
        },
        stop: function (event, ui) {
          $('iframe').show();
        },

        receive: function (event, ui) {
          var nid = ui.item.data('nid');
          var sortable = this;

          $.get(settings.basePath + 'admin/cart/' + nid + '/add')
            .done(function (data) {
              // Add to cart list
              var elem = '<div class="ucms-cart-item col-md-6" draggable="true" data-nid="' + nid + '">' + data.node + '</div>';
              $(elem).appendTo('#ucms-cart-list');
              $(sortable).sortable("refresh");
            })
            .fail(function (xhr) {
              // display error and revert
              var err_elem = '<div class="alert alert-danger">' + xhr.responseJSON.error + '</div>';
              var $errElem = $(err_elem).appendTo('#ucms-cart-list');
              setTimeout(function () {
                $errElem.fadeOut(function () {
                  $(this).remove();
                });
              }, 3000);
              event.preventDefault();
            });
        },

        update: function () {
          // Cancel any sort as there is no point to retain positions
          $(this).sortable('cancel');
        }
      }));

      if (settings.ucmsLayout) {
        /**
         * Regions: drop zones for cart items and for other zones
         */
        $('[data-region]', context).sortable($.extend({}, Drupal.ucmsSortableDefaults, {
          // Connect with others regions and trash
          connectWith: '[data-region], #ucms-cart-trash',
          appendTo: document.body,
          helper: 'clone',

          start: function (event, ui) {
            // Add some useful info to items for update() and receive()
            ui.item.startPos = ui.item.index();
            ui.item.originRegion = $(this).data('region');
          },

          beforeStop: function (event, ui) {
            Drupal.ucmsTempReceivedPos = ui.helper.index();
            // Need to save the fact that we are receiving or not for update()
            Drupal.ucmsIsSameContainer = !!$(ui.placeholder).closest(this).length;
          },

          receive: function (event, ui) {
            var sortable = this,
            // Replace element on receiving, with the correct view mode, at the
            // correct position
              replaceElementWithData = function (data) {
                // TODO region item theming should be done in ajax callback
                var elem = '<div class="ucms-region-item" data-nid="' + ui.item.data('nid') + '">' + data.node + '</div>';
                if (ui.item.justReceived) {
                  var olderBrother = $(sortable).find("> *:nth-child(" + (Drupal.ucmsTempReceivedPos + 1) + ")");
                  if (olderBrother.length) {
                    olderBrother.before(elem);
                  } else {
                    $(sortable).append(elem);
                  }
                  $(sortable).sortable('refresh');
                } else {
                  $(ui.item).replaceWith(elem);
                }
              },
              opts = {
                region: $(this).data('region'),
                nid: ui.item.data('nid'),
                position: Drupal.ucmsTempReceivedPos, // Don't ask me why
                token: settings.ucmsLayout.editToken
              },
              action = 'add';
            if (ui.item.originRegion) {
              // Move from previous region
              opts.prevRegion = ui.sender.data('region');
              opts.prevPosition = ui.item.index();
              action = 'move';
            }
            else {
              ui.item.justReceived = true;
            }
            $.post(settings.basePath + 'ajax/ucms/layout/' + settings.ucmsLayout.layoutId + '/' + action, opts).done(replaceElementWithData);
          },

          update: function (event, ui) {
            // Do nothing if we are not in the same container
            if (!Drupal.ucmsIsSameContainer) {
              return;
            }

            $.post(settings.basePath + 'ajax/ucms/layout/' + settings.ucmsLayout.layoutId + '/move', {
              region: $(this).data('region'),
              nid: ui.item.data('nid'),
              position: ui.item.index(),
              prevPosition: ui.item.startPos,
              token: settings.ucmsLayout.editToken
            });
          }
        }));
      }

      /**
       * Last drop zone, trash, accepting cart items or region items
       */
      $('#ucms-cart-trash', context).sortable($.extend({}, Drupal.ucmsSortableDefaults, {
        receive: function (event, ui) {
          var nid = ui.item.data('nid');
          if (ui.item.hasClass('ucms-cart-item')) {
            // Remove form cart
            $.get(settings.basePath + 'admin/cart/' + nid + '/remove')
              .done(function () {
                ui.item.remove();
              });
          }
          else {
            // Remove from region
            $.post(settings.basePath + 'ajax/ucms/layout/' + settings.ucmsLayout.layoutId + '/remove', {
              region: ui.item.originRegion,
              position: ui.item.startPos,
              token: settings.ucmsLayout.editToken
            }).done(function () {
              ui.item.remove();
            });
          }
        },
        over: function () {
          $(this).addClass('ucms-highlighted-hover');
        },
        out: function () {
          $(this).removeClass('ucms-highlighted-hover');
        }
      }));
    }
  };
}(jQuery));
