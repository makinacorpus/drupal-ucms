(function ($, Drupal) {
  "use strict";

  // @todo
  var refreshUrl = '/admin/page/refresh';

  /**
   * Spawn modal while loading
   *
   * @param object page
   */
  function modalSpawn(page) {
    page.modal.css({
      display: "block"
    });
  }

  /**
   * Destroy modal once loaded
   *
   * @param object page
   */
  function modalDestroy(page) {
    page.modal.css({
      display: "none"
    });
  }

  /**
   * Parse query string from the given URL (complete or not)
   *
   * If no "?" char is found, it treats the string as a query string without
   * hostname, invalid entries (without "=" char) will be set as keys in the
   * return object with an empty string value.
   *
   * @param string uri
   *
   * @returns object
   */
  function parseLink(uri) {
    if (uri === "") {
      return {};
    }

    var pos = uri.indexOf('?');
    if (-1 !== pos) {
      uri = uri.substr(pos + 1);
    }

    var ret = {};
    uri.split("&").forEach(function (raw) {
      var pos = raw.indexOf("=");
      if (-1 === pos) {
        ret[raw] = "";
      } else {
        var key = raw.substr(0, pos);
        var value = raw.substr(pos + 1);
        ret[key] = decodeURIComponent(value.replace(/\+/g, " "));
      }
    });

    return ret;
  }

  /**
   * From the given AJAX response, update the current page state and redraw
   * the given blocks.
   *
   * @param object page
   * @param object response
   */
  function placePageBlocks(page, response) {
    if (response.query) {
      page.query = response.query;
    }
    if (response.blocks) {
      $.each(response.blocks, function(index, value) {
        var block = page.selector.find('[data-page-block=' + index + ']');

        if (!block.length) {
          console.log("Warning, block " + index + " does not exists in page");
        } else if (1 < block.length) {
          console.log("Warning, block " + index + " exists more than once in page");
        }

        var partialDom = $(value);
        attachBehaviors(page, partialDom);

        block.html(partialDom);
      });
    }
  }

  /**
   * Refresh the page by sending an AJAX query with the new query
   *
   * @param object page
   * @param object query
   *   Overrides to apply on the current page state
   * @param boolean dropAll
   *   If true, page will be reloaded using the given query parameter without
   *   using the current stored state: useful for links because they already
   *   have been built using all the query parameters.
   */
  function refreshPage(page, query, dropAll) {
    // Avoid infinite recursion and multiple orders at the same time
    if (page.refreshing) {
      return;
    }
    page.refreshing = true;
    modalSpawn(page);
    // Rebuild correct query data from our state.
    var data = {};
    if (!dropAll) {
      $.each(page.query, function(index, value) {
        data[index] = value;
      });
    }
    // Then override using the incomming one.
    if (query) {
      $.each(query, function (index, value) {
        data[index] = value;
      });
    }
    // For consistency ensure the page identifier is the right one.
    data.name = page.id;

    $.ajax(refreshUrl, {
      method: 'get',
      cache: false,
      data: data,
      success: function (response) {
        placePageBlocks(page, response);
      },
      error: function () {
        // refresh the page manually
      },
      complete: function () {
        modalDestroy(page);
        page.refreshing = false;
      }
    });
  }

  /**
   * Re-attach current page page and Drupal behaviours on a replaced block
   *
   * @param object page
   * @param object context
   *   Partial DOM created from the new block raw HTML
   */
  function attachBehaviors(page, context) {
    // Ajax on links
    page.selector.find('[data-page-link]').on("click", function (event) {
      event.stopPropagation();

      // Links have a pre-built query that should work
      var query = parseLink(this.href);
      refreshPage(page, query, true);

      return false;
    });

    // Type watch on search
    if (page.searchParam) {
      var form = page.selector.find('form.ucms-dashboard-search-form');
      if (form.length) {
        var input = form.find('input[type=text]');
        input.typeWatch({
          callback: function (value) {
            var query = {};
            query[page.searchParam] = value;
            refreshPage(page, query);
          },
          allowSubmit: true,
          captureLength: 0,
          wait: 750
        });
        input.on("change", function () {
          if ("" === this.value) {
            var query = {};
            query[page.searchParam] = "";
            refreshPage(page, query);
          }
        });
      }
    }

    Drupal.attachBehaviors(context);
  }

  /**
   * Drupal behavior, find pages, spawn them, attach their behaviours.
   */
  Drupal.behaviors.UcmsDashboardPage = {
    attach: function (context, settings) {
      $(context).find("[data-page]").once('ucms_dashboard_page', function () {

        var selector = $(this);
        var page = {
          selector: selector,
          query: JSON.parse(selector.attr('data-page-query')),
          id: selector.attr('data-page'),
          searchParam: selector.attr('data-page-search'),
          refreshing: false
        };

        attachBehaviors(page, page.selector);

        // Spawn the modal once for all.
        var modal = $('<div class="page-modal"></div>');
        modal.css({
          display: "none",
          background: "black",
          opacity: 0.4,
          position: "absolute",
          top: 0,
          left: 0,
          bottom: 0,
          right: 0,
          "z-index": 1000
        });
        selector.append(modal);
        page.modal = modal;
      });
    }
  };

}(jQuery, Drupal));
