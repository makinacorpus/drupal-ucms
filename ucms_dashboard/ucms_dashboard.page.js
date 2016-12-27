(function ($) {

  /**
   * Build and admin page object
   *
   * @param element
   */
  function Page(element) {
    var name = element.getAttribute("data-page");
  }

  Drupal.behaviors.UcmsDashboardPage = {
    attach: function (context, settings) {
      var nodes = context.querySelectorAll("[data-page]:not([data-page-init])");
      var i;
      for (i in nodes) {
        // Sometimes, nodes are not nodes
        if (nodes[i].getAttribute) {
          new Page(nodes[i]);
          nodes[i].setAttribute("data-page-init", 1);
        }
      }
    }
  };

}(jQuery));
