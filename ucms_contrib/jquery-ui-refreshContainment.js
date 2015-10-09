/*
 * jQuery UI RefreshContainment v0.1
 *
 * A plugin for jQuery UI's Draggable. It adds a refreshContainment method to
 * every draggable which allows you to use the containment option on draggables
 * with dynamically changing sizes.
 *
 * Depends:
 *  jquery.ui.core.js
 *  jquery.ui.widget.js
 *  jquery.ui.mouse.js
 *  jquery.ui.draggable.js
 */

(function ($) {
  var $window = $(window);

  // We need to know the location of the mouse so that we can use it to
  // refresh the containment at any time.

  $window.data("refreshContainment", {mousePosition: {pageX: 0, pageY: 0}});
  $window.mousemove(function (event) {
    $window.data("refreshContainment", {
      mousePosition: {pageX: event.pageX, pageY: event.pageY}
    });
  });

  // Extend draggable with the proxy pattern.
  var proxied = $.fn.draggable;
  $.fn.draggable = (function (method) {
    if (method === "refreshContainment") {
      this.each(function () {
        var inst = $(this).data("uiDraggable");

        // Check if the draggable is already being dragged.
        var isDragging = inst.helper && inst.helper.is(".ui-draggable-dragging");

        // We are going to use the existing _mouseStart method to take care of
        // refreshing the containtment but, since we don't actually intend to
        // emulate a true _mouseStart, we have to avoid any extraneous
        // operations like the drag/drop manager and event triggering.
        // So we save the original member values and replace them with dummies.
        var ddmanager = $.ui.ddmanager;
        $.ui.ddmanager = null;
        var trigger = inst._trigger;
        inst._trigger = function () {
          return true;
        };


        var mousePosition = $window.data("refreshContainment").mousePosition;
        var fakeEvent = {
          pageX: mousePosition.pageX, pageY: mousePosition.pageY
        };
        inst._mouseStart(fakeEvent);

        // Return those extraneous members back to the original values.
        inst._trigger = trigger;
        $.ui.ddmanager = ddmanager;

        // Clear the drag, unless it was already being dragged.
        if (!isDragging) {
          inst._clear();
        }
      });
      return this;
    }
    else {
      // Delegate all other calls to the actual draggable implemenation.
      return proxied.apply(this, arguments);
    }
  });
})(jQuery);
