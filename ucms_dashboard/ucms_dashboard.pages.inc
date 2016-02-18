<?php
/**
 * @file
 * Main dashboard pages.
 */

/**
 * Main dashboard page.
 */
function ucms_dashboard_page() {
  $dashboard = ucms_dashboard_portlet_registry_get();

  return [
    '#theme'  => 'ucms_dashboard_page',
    '#portlets' => $dashboard->getPortletsForAccount(clone $GLOBALS['user']),
  ];
}
