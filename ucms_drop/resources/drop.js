/**
 * µCMS drap'n'drop API.
 */

var Ucms = Ucms || {};

(function (Drupal, document, dragula) {
  "use strict";

  if (!dragula) {
    return;
  }

  var droppables = [];
  var draggables = [];

  // @todo
  //  - define object structures,
  //  - type things,
  //  - make everything safe,
  //  - ...

  /**
   * Log something to console
   *
   * @param string message
   * @param object object
   */
  function log(message, object) {
    if (message && console && console.log) {
      console.log(message);
      if (object) {
        console.log(object);
      }
    }
  }

  /**
   * Is the found element already registered
   *
   * @param DOMNode element
   * @return boolean
   */
  function isDroppableRegistered(element) {
    var ret = false;
    droppables.forEach(function (item) {
      if (item.element === element) {
        ret = true;
      }
    });
    return ret;
  }

  /**
   * Is the found element already registered
   *
   * @param DOMNode element
   * @return boolean
   */
  function isDraggableRegistered(element) {
    var ret = false;
    draggables.forEach(function (item) {
      if (item.element === element) {
        ret = true;
      }
    });
    return ret;
  }

  /**
   * Find elements
   *
   * @param DOMNode element
   *   Node in which to search, may be document
   * @param string type
   *   One of "droppable" or "draggable"
   *
   * @return object[]
   */
  function findAll(element, type) {
    var ret = [];

    if (!type) {
      throw "You must specify the type parameter";
    }
    if ("droppable" !== type && "draggable" !== type) {
      throw "Type must be 'droppable' or 'draggable'";
    }
    if (!element) {
      element = document;
    }

    var typeAttr = "data-" + type + "-type";
    var idAttr = "data-" + type + "-id";
    var filterCallback;

    switch (type) {
      case "droppable":
        filterCallback = isDroppableRegistered;
        break;
      case "draggable":
        filterCallback = isDraggableRegistered;
        break;
    }

    // Find all using data attributes
    element
      .querySelectorAll("*[" + typeAttr + "]")
      .filter(filterCallback)
      .forEach(function (node) {
        if (node) {
          var id = node.getAttribute(idAttr);
          if (id) {
            ret.push({
              element: node,
              type: node.getAttribute(typeAttr),
              id: id
            });
          } else {
            log("found an element with " + typeAttr + " but " + idAttr + " attribute found:", node);
          }
        }
      })
    ;

    // Register dragula behavior for containers
    if ("droppable" === type && drake) {
      ret.forEach(function (object) {
        drake.containers.push(object.element);
      });
    }

    return ret;
  }

  /**
   * Parse element dom portion and add the found elements to registry
   *
   * @param DOMNode element
   */
  function init(element) {
    droppables = droppables.concat(findAll(element, "droppable"));
    draggables = draggables.concat(findAll(element, "draggable"));
  }

  /**
   * Dragula moves() callback
   */
  function dragulaMoves(element, source, handle, sibling) {
    // @todo ajax
  }

  /**
   * Dragula accepts() callback
   */
  function dragulaAccepts(element, source, handle, sibling) {
    // @todo ajax
  }

  /**
   * Dragula invalid() callback, this should prevent any non-registered
   * draggable to be dragged since we are handling those by ourselves.
   */
  function dragulaInvalid(element, handle) {
    var ret = false;
    draggables.forEach(function (object) {
      if (object.element === element) {
        ret = true;
      }
    });
    return ret;
  }

  /**
   * dragula object, this where it all happens.
   */
  var drake = dragula({
    copy: true,
    revertOnSpill: true,
    removeOnSpill: true,
    moves: dragulaMoves,
    accepts: dragulaAccepts,
    invalid: dragulaInvalid
  });

  /**
   * Drupal behavior, because we do are using Drupal, this is, and will remain,
   * outside of Dragula itself, the only external dependency we have.
   */
  Drupal.behaviors.UcmsDrop = {
    attach: function (context, settings) {
      init(context);
    }
  };

}(Drupal, document, dragula));
