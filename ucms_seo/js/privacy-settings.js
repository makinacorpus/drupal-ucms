/**
 * Privacy settings for analytics provider.
 *
 * Client can opt-in/opt-out for any provider, meaning that he can disabled
 * being tracked. In order for this to be possible, you should provide such
 * link in your HTML content:
 */
var UcmsSeoPrivacy = (function (document, $) {
  "use strict";

  /**
   * Is provider disabled by client (opt-out)
   *
   * @param string provider
   *
   * @returns boolean
   */
  function isOptOut(provider) {
    return (new RegExp('privacy_' + provider + '=1')).test(document.cookie);
  }

  /**
   * Opt-int analytics provider
   *
   * @param string provider
   */
  function optIn(provider) {
    document.cookie = "privacy_" + provider + "=0";
  }

  /**
   * Opt-out analytics provider
   *
   * @param string provider
   */
  function optOut(provider) {
    document.cookie = "privacy_" + provider + "=1";
  }

  /**
   * Is client first visit
   *
   * @returns boolean
   */
  function isFirstVisit() {
    return !(new RegExp('cookie_consent=')).test(document.cookie);
  }

  /**
   * Initialize cookie popup component
   */
  function initCookieComponent(context, settings) {
    var selector = settings.selector || "#cookies";
    var element = document.querySelector(selector);

    if (!element) {
      return;
    }

    var closeButtonNodes = element.querySelectorAll('.close-cookie-dialog');
    for (var i = 0; i < closeButtonNodes.length; ++i) {
      var closeButton = closeButtonNodes[i];
      closeButton.addEventListener("click", function (event) {
        event.preventDefault();
        document.cookie = "cookie_consent=1";
        $(element).fadeOut();
      });
    }

    if (isFirstVisit()) {
      $(element).show();
    } else {
      $(element).hide();
    }
  }

  /**
   * Initialize privacy settings component
   */
  function initPrivacySettingsComponent() {
    var checkboxes = document.querySelectorAll('input[type=checkbox].ucms-privacy-opt-out');

    if (checkboxes) {
      for (var i = 0; i < checkboxes.length; ++i) {
        var checkbox = checkboxes[i];
        if (checkbox.hasAttribute("rel")) {

          if (isOptOut(checkbox.getAttribute("rel"))) {
            checkbox.checked = "checked";
          } else {
            checkbox.checked = "";
          }

          checkbox.addEventListener("change", function (event) {
            if (this.checked) {
              optOut(this.getAttribute("rel"));
              checkbox.checked = "checked";
            } else {
              optIn(this.getAttribute("rel"));
              checkbox.checked = "";
            }
          });
        }
      }
    }
  }

  /**
   * Main Drupal behavior
   */
  Drupal.behaviors.PrivacySettings = {
    attach: function (context, settings) {
      var privacySettings = settings.privacy || {};
      initCookieComponent(context, privacySettings);
      initPrivacySettingsComponent(context, privacySettings);
    }
  };

  return {
    isOptOut: isOptOut,
    optIn: optIn,
    optOut: optOut
  };

}(document, jQuery));