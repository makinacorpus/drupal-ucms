<?php
/**
 * @file
 * Main dashboard pages.
 */

/**
 * Main dashboard page.
 */
function ucms_dashboard_page() {
  return [
    '#theme'  => 'ucms_dashboard_page',
    '#portlets' => ucms_dashboard_portlet_registry_get()->getPortletsForAccount(\Drupal::currentUser()),
  ];
}
