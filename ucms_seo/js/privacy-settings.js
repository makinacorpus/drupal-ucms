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
   * Is tracking disabled by client (opt-out)
   *
   * @returns boolean
   */
  function isOptOut() {
    return (new RegExp('privacy_tracker=1')).test(document.cookie);
  }

  /**
   * Opt-int analytics tracker
   */
  function optIn() {
    document.cookie = "privacy_tracker=0";
  }

  /**
   * Opt-out analytics tracker
   */
  function optOut() {
    document.cookie = "privacy_tracker=1";
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
    var radio_yes = document.querySelector("input[type=radio].ucms-privacy-yes");
    var radio_no = document.querySelector("input[type=radio].ucms-privacy-no");

    if (radio_yes && radio_no) {
      if (isOptOut()) {
        radio_yes.checked = "checked";
        radio_no.checked = "";
      } else {
        radio_yes.checked = "";
        radio_no.checked = "checked";
      }

      radio_yes.addEventListener("change", function (event) {
        if (this.checked) {
          optOut();
        } else {
          optIn();
        }
      });
      radio_no.addEventListener("change", function (event) {
        if (this.checked) {
          optIn();
        } else {
          optOut();
        }
      });
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