(function ($, Drupal) {
  "use strict";

  function fetchResults(container, url, string) {
    $.ajax({
      url: url,
      data: {
        s: string
      },
      success: function (data) {
        populateContainer(container, data.items);
      },
      dataType: 'json'
    });
  }

  function populateContainer(container, items) {
    container.html(" ");
    if (items) {
      items.forEach(function (item) {
        container.append(item);
      });
    }
  }

  Drupal.behaviors.UcmsContribSearchPane = {

    attach: function (context, settings) {

      if (!settings.UcmsContrib.searchPaneUrl) {
        return;
      }

      $(context).find('#search-pane').once('search-pane', function () {
        var pane = $(this);
        var container = pane.find('.results');
        var tip = pane.find('.tip');

        pane.find('input').each(function () {
          var input = $(this);

          input.typeWatch({
            callback: function () {
              if (tip) {
                tip.hide();
              }
              fetchResults(container, settings.UcmsContrib.searchPaneUrl, input.val());
            },
            wait: 600,
            captureLength: 3
          });
        });
      });
    }
  };
}(jQuery, Drupal));
