(function ($, Drupal, dragula) {
  "use strict";

  function isSortable(element) {
    var sortable = element.getAttribute("data-sortable");
    if (sortable) {
      return 0 < parseInt(sortable, 10);
    }
    return false;
  }

  function accepts(element, target, source, sibling) {
    return isSortable(target);
  }

  function moves(element, source, handle, siblings) {
    return true; // elements are always draggable by default
  }

  function invalid(element, handle) {
    var nodeId = element.getAttribute("data-nid");
    if (nodeId) {
      return 0 < parseInt(nodeId, 10);
    }
    return false;
  }

  /*
  function isContainer(element) {
    // Only pre-registered contains are allowed.
    return false;
  }
   */

  function copy(element, source) {
    return !isSortable(source);
  }

  function fetchThumnail(element, container, source) {
    //
  }

  /**
   * Drupal behavior
   */
  Drupal.behaviors.ucmsCart = {
    attach: function (context, settings) {

      var containers = [];

      $(context).find('[data-sortable], [data-drag]').once(function() {
        containers.push(this);
      });

      // And for that matters, other too
      if (containers.length) {
        var drake = dragula(containers, {
          // Custom callbacks
          // isContainer: isContainer,
          //moves: moves,
          accepts: accepts,
          //invalid: invalid,
          direction: 'horizontal',
          // We always copy when we move things from a
          copy: copy,
          copySortSource: true,
        });
        console.log("coucou!");
      }
    }
  };

}(jQuery, Drupal, dragula));
