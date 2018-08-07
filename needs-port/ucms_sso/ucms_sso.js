// No jQuery dependency else we will have problems.
(function () {
  "use strict";
  var d = document;
  // Fetch unix timestamp.
  function n() {
    return Math.floor(Date.now() / 1000);
  }
  // Tell the browser to stop checking for a while.
  function stop() {
    d.cookie = "usso=" + (n() + 240); // 3 minutes
  }
  // Can the broswer attempt?
  function may() {
    var match = d.cookie.match(/usso=([\d]+)(;|$)/);
    return !match || match[1] < n();
  }
  if (may()) {
    var x = new XMLHttpRequest();
    x.open("GET", "${master}");
    x.withCredentials = true;
    x.setRequestHeader("Accept", "application/json");
    x.onerror = function () {
      stop();
    };
    x.onload = function () {
      try {
        var d = JSON.parse(this.responseText);
        if (d.status && d.uri) {
          window.location.href = d.uri + "&destination=" + window.location.href;
        }
        stop();
      } catch (e) {
        stop();
      }
    };
    x.send();
  }
}());