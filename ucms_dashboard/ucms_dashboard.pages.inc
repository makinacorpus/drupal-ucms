<?php
/**
 * @file
 * Main dashboard pages.
 */

/**
 * Main dashboard page.
 */
function ucms_dashboard_page() {
  $dashboard = ucms_dashboard_dashboard_get()->init();

  return [
    '#theme'  => 'ucms_dashboard_page',
    '#portlets' => $dashboard->getPortlets(),
  ];
}
