(function ($) {

  /**
   * Build and admin page object
   *
   * @param element
   */
  function Page(element) {
    var name = element['data-pname'];
  }

  Drupal.behaviors.UcmsDashboardPage = {
    attachBehaviors: function (context, settings) {
      var nodes = context.querySelectorAll("[data-pname]:not([data-pninit])");
      var i;
      for (i in nodes) {
        new Page(nodes[i]);
        nodes[i]['data-pinit'] = "true";
      }
    }
  };

}(jQuery));
