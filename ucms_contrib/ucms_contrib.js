(function ($) {
  "use strict";

  // This variable is used because of this bug https://bugs.jqueryui.com/ticket/4303
  // so as workaround, we save the helper and its position before it's removed
  Drupal.ucmsTempReceivedPos = 0;
  Drupal.ucmsIsSameContainer = false;

  var UcmsCart;
  var currentSelection;
  var drupalSettings = {};

  // Run this once, so this OK to do it from here
  document.addEventListener("keydown", function (event) {
    if (46 === event.keyCode) { // Delete key
      onDeletePress(event);
    }
    if (27 === event.keyCode) {
      onEscapePress(event);
    }
  });

  /**
   * Select element
   */
  function selectElement(element) {
    if (currentSelection) {
      currentSelection.removeAttribute('data-selected');
    }
    element.setAttribute('data-selected', true);
    currentSelection = element;
  }

  /**
   * Clear current selection
   */
  function clearSelection() {
    if (currentSelection) {
      currentSelection.removeAttribute('data-selected');
      currentSelection = null;
      return true;
    }
    return false;
  }

  /**
   * On escape press, remove current selection
   */
  function onEscapePress(event) {
    if (clearSelection()) {
      // Cancel propagation only if we have something legitimate to do, but
      // leave any other code do whatever it needs to do if we don't
      event.stopPropagation();
      event.preventDefault();
    }
  }

  /**
   * Get element position within parent
   *
   * @return number
   */
  function getElementPosition(element) {
    var i = 0, count = 0;
    for (i; i < element.parentElement.children.length; ++i) {
      var child = element.parentElement.children[i];

      // Don't count other DOM element.
      if (child.getAttribute('data-nid') === null) {
        continue;
      } else if (child === element) {
        break;
      }
      count++;
    }
    return count;
  }

  /**
   * Delete element from DOM, send the trash event
   *
   * Weird signature, but first is DOMElement, second is jQuery selector
   *
   * @param DOMElement element
   * @param undefined|jQuery sortable
   */
  function trashElement(element) {
    var nid = element.getAttribute('data-nid');

    if (!nid) {
      if (console && console.log) {
        console.log("attempt to trash a non-managed item");
      }
      return false;
    }

    var target = $(element);
    var sortable = target.closest('.ui-sortable');
    var region = sortable.data('region');

    if (target.hasClass('ucms-cart-item')) {
      var getURL = drupalSettings.basePath + 'admin/cart/' + nid + '/remove';
      $.get(getURL).done(function () {
        target.remove();
      });
      return true;
    } else {
      // Remove from region
      if (!region) {
        if (console && console.log) {
          console.log("attempt to trash a non-managed item");
        }
        return false;
      }
      var payload = {
        region: region,
        position: getElementPosition(element),
        token: drupalSettings.ucmsLayout.editToken
      };
      var postURL = drupalSettings.basePath + 'ajax/ucms/layout/' + drupalSettings.ucmsLayout.layoutId + '/remove';
      $.post(postURL, payload).done(function () {
        target.remove();
      });
      return true;
    }

    return false;
  }

  /**
   * Appy behaviors on items within the given container
   */
  function connectItems(container) {
    var elements = container.querySelectorAll("[data-nid]");
    elements = Array.prototype.slice.call(elements, 0);
    elements.forEach(function (node) {
      node.addEventListener("click", function (event) {
        selectElement(node);
        event.stopPropagation();
        event.preventDefault();
      });
    });
  }

  /**
   * Remove current item (move to trash)
   */
  function onDeletePress(event) {
    if (currentSelection && trashElement(currentSelection)) {
      // Cancel propagation only if we have something legitimate to do, but
      // leave any other code do whatever it needs to do if we don't
      event.stopPropagation();
      event.preventDefault();
      if (currentSelection) {
        trashElement(currentSelection);
      }
      clearSelection();
    }
  }

  /**
   * Refresh sortable for when you added new items
   */
  function refreshSortable(sortable) {
    $(sortable).sortable('refresh');
    connectItems(sortable);
    Drupal.attachBehaviors(sortable);
  }

  $.fn.extend({
    /**
     * Used through a Drupal ajax command
     */
    UcmsCartAdd: function (data) {
      if (data.output) {
        // Add item to list
        var elem = '<div class="ucms-cart-item" data-nid="' + data.nid + '">' + data.output + '</div>';
        $(elem).appendTo('#ucms-cart-list');
        refreshSortable(UcmsCart);
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

      // This allow higher scope functions to use it
      drupalSettings = $.extend(drupalSettings, settings);

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
              var elem = '<div class="ucms-cart-item" draggable="true" data-nid="' + nid + '">' + data.output + '</div>';
              $(elem).appendTo('#ucms-cart-list');
              refreshSortable(sortable);
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
            var sortable = this;

            // Replace element on receiving, with the correct view mode, at the
            // correct position
            var replaceElementWithData = function (data) {
              // TODO region item theming should be done in ajax callback
              if (data.output) {
                if (ui.item.justReceived) {
                  var olderBrother = $(sortable).find("> *:nth-child(" + (Drupal.ucmsTempReceivedPos + 1) + ")");
                  if (olderBrother.length) {
                    olderBrother.before(data.output);
                  } else {
                    $(sortable).append(data.output);
                  }
                } else {
                  $(ui.item).replaceWith(data.output);
                }
                refreshSortable(sortable);
              }
            };

            var opts = {
              region: $(this).data('region'),
              nid: ui.item.data('nid'),
              position: Drupal.ucmsTempReceivedPos, // Don't ask me why
              token: settings.ucmsLayout.editToken
            };

            var action = 'add';
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
          trashElement(ui.item.get(0));
        },
        over: function () {
          $(this).addClass('ucms-highlighted-hover');
        },
        out: function () {
          $(this).removeClass('ucms-highlighted-hover');
        }
      }));

      $('#ucms-cart-list').each(function () { connectItems(this); });
      $('[data-can-receive]').each(function () { connectItems(this); });
    }
  };
}(jQuery));
