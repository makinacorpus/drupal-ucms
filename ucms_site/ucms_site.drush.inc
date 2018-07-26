<?php

/**
 * Implements hook_drush_command().
 */
function ucms_site_drush_command() {
  $items = [];

  $items['node-access-rebuild'] = [
    'callback'    => 'ucms_site_drush_node_access_rebuild',
    'description' => "Rebuild node access for all nodes.",
    'bootstrap'   => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  ];

  return $items;
}

/**
 * Rebuild all node grants.
 */
function ucms_site_drush_node_access_rebuild() {

  if (!drush_confirm("This may cause temporary display issues on the site, continue ?")) {
    return;
  }

  $total  = db_query("SELECT COUNT(*) FROM {node_field_data}")->fetchField();
  if (!$total) {
    drush_print("No node access to rebuild.");
    return;
  }

  $limit  = 50;
  $done   = 0;
  $latest = 0;
  $bar    = \DrushProgressBar::create();

  // Got a lot of nodes, so let's load them bulk.
  $bar->start();
  do {
    $idList = db_query("SELECT nid FROM {node_field_data} WHERE nid > :max ORDER BY nid ASC LIMIT " . $limit, [':max' => $latest])->fetchCol();

    foreach (node_load_multiple($idList) as $node) {
      $latest = $node->nid;

      node_access_acquire_grants($node);
    }

    $done += count($idList);
    $bar->update($done / $total);

  } while ($idList);

  $bar->end();
}
