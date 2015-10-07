(function ($) {
  Drupal.behaviors.ucmsCart = {
    attach: function (context, settings) {
      $('#ucms-cart').droppable({
        accept: "[data-nid]:not(.ucms-cart-item)",
        hoverClass: "drop-highlighted-hover",
        activate: function (event, ui) {
          $(this).addClass('drop-highlighted');
        },
        deactivate: function (event, ui) {
          $(this).removeClass('drop-highlighted');
        },
        drop: function (event, ui) {
          var nid = ui.draggable.data('nid');
          $.get(settings.basePath + 'admin/cart/' + nid + '/add/nojs')
            .done(function (data) {
              // add to cart list
              var elem = '<div class="ucms-cart-item col-md-6" data-nid="' + nid + '">' + data.node + '</div>';
              $('#ucms-cart-list').append(elem).find('div:last-child').draggable({
                revert: 'invalid',
                opacity: 0.75
              });
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
      });
      $('#ucms-cart-trash').droppable({
        accept: "[data-nid].ucms-cart-item",
        hoverClass: "drop-highlighted-hover",
        activate: function (event, ui) {
          $(this).addClass('drop-highlighted');
        },
        deactivate: function (event, ui) {
          $(this).removeClass('drop-highlighted');
        },
        drop: function (event, ui) {
          var nid = ui.draggable.data('nid');
          $.get(settings.basePath + 'admin/cart/' + nid + '/remove/nojs')
            .done(function () {
              // remove from cart
              ui.draggable.remove();
            });
        }
      });
      $('[data-nid]').draggable({
        revert: true,
        opacity: 0.75
      });
    }
  };
}(jQuery));
