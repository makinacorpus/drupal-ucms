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
  var acceptCache = {};

  // @todo
  //  - define object structures,
  //  - type things,
  //  - make everything safe,
  //  - ...

  function acceptCacheGet(droppable, draggable) {
    var key = [droppable.ucmsDropType, droppable.ucmsDropId, draggable.ucmsDropType, draggable.ucmsDropId].join(';');
    return acceptCache[key];
  }

  function acceptCacheSet(droppable, draggable, value) {
    var key = [droppable.ucmsDropType, droppable.ucmsDropId, draggable.ucmsDropType, draggable.ucmsDropId].join(';');
    acceptCache[key] = value;
  }

  function ajaxBuildPayload(options) {
    
  }

  /**
   * Push an ajax query
   */
  function ajax(method, droppable, draggable, options, onSuccess, onError, async) {
    if (!method) {
      throw "missing 'method' argument";
    }
    if (!droppable) {
      throw "missing 'droppable' argument";
    }
    if (!draggable) {
      throw "missing 'draggable' argument";
    }
    if (!onSuccess) {
      log("ajax() method should probably be called using a onSuccess callback");
    }
    if (undefined === async) {
      async = true;
    }

    options.type = draggable.ucmsDropType;
    options.id = draggable.ucmsDropId;

    // @todo csrf token?
    var xhr = new XMLHttpRequest();
    xhr.open("POST", 'ucms/drop/ajax/' + method + '/' + droppable.ucmsDropType + '/' + droppable.ucmsDropId, async);
    xhr.setRequestHeader("Accept", "application/json");
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.onerror = function() {
      var response;
      try {
        response = JSON.parse(this.responseText);
        log("error during ajax call, parsed JSON:", response);
      } catch (e) {
        log("error during ajax call, could not parse JSON:", this.responseText);
      }
    };

    xhr.onload = function() {
      var response;
      try {
        response = JSON.parse(this.responseText);
      } catch (e) {
        log("ajax call success but could not parse JSON:", this.responseText);
        return;
      }

      // I do have no idea why this appens, but it does, a few errors in
      // firefox are considered as valid...
      if (200 !== this.status) {
        log("ajax call success but response is " + this.status + ":", this.responseText);
        return;
      }

      // Else normal file processing
      if (onSuccess) {
        onSuccess(response);
      }
    };

    xhr.send(JSON.stringify(options));
  }

  /**
   * Alias of querySelectorAll() that returns an array instead of a NodeList
   */
  function query(element, selector) {
    var ret = [];
    if (!element) {
      element = document;
    }
    var elements = element.querySelectorAll(selector);
    var i = 0;
    for (i; i < elements.length; ++i) {
      ret.push(elements[i]);
    }
    return ret;
  }

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
  function isNotDroppableRegistered(element) {
    return !droppables.some(function (node) {
      return node === element;
    });
  }

  /**
   * Is the found element already registered
   *
   * @param DOMNode element
   * @return boolean
   */
  function isNotDraggableRegistered(element) {
    return !draggables.some(function (node) {
      return node === element;
    });
  }

  /**
   * Find elements
   *
   * @param DOMNode element
   *   Node in which to search, may be document
   * @param string role
   *   One of "droppable" or "draggable"
   *
   * @return DOMNode[]
   */
  function findAll(element, role) {
    var ret = [];

    if (!role) {
      throw "You must specify the type parameter";
    }
    if ("droppable" !== role && "draggable" !== role) {
      throw "Role must be 'droppable' or 'draggable'";
    }
    if (!element) {
      element = document;
    }

    var typeAttr = "data-" + role + "-type";
    var idAttr = "data-" + role + "-id";
    var filterCallback;

    switch (role) {
      case "droppable":
        filterCallback = isNotDroppableRegistered;
        break;
      case "draggable":
        filterCallback = isNotDraggableRegistered;
        break;
    }

    // Find all using data attributes
    query(element, "*[" + typeAttr + "]")
      .filter(filterCallback)
      .forEach(function (node) {
        if (node) {
          var id = node.getAttribute(idAttr);
          if (id) {
            node.ucmsDropRole = role;
            node.ucmsDropType = node.getAttribute(typeAttr);
            node.ucmsDropId = id;
            ret.push(node);
          } else {
            log("found an element with " + typeAttr + " but " + idAttr + " attribute found:", node);
          }
        }
      })
    ;

    // Register dragula behavior for containers
    if ("droppable" === role && drake) {
      ret.forEach(function (node) {
        drake.containers.push(node);
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
  function dragulaAccepts(element, target, source, sibling) {
    if (target !== source && "droppable" === target.ucmsDropRole && "draggable" === element.ucmsDropRole) {
      var cached = acceptCacheGet(target, element);
      if (undefined !== cached) {
        return !!cached;
      }
      ajax(
        'accepts',
        target,
        element,
        {},
        function (response) {
          // what?!!
          console.log("coucou");
        },
        null,
        false
      );
    }
    return false;
  }

  /**
   * Dragula invalid() callback, this should prevent any non-registered
   * draggable to be dragged since we are handling those by ourselves.
   */
  function dragulaInvalid(element, handle) {
    //return "draggable" !== element.ucmsDropRole;
    return false;
  }

  /**
   * dragula object, this where it all happens.
   */
  var drake = dragula({
    copy: true,
    //revertOnSpill: true,
    //removeOnSpill: true,
    //moves: dragulaMoves,
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
