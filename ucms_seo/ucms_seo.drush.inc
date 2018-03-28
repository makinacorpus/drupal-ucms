<?php
/**
 * @file
 * Drush integration.
 */

/**
 * Implements hook_drush_command().
 */
function ucms_seo_drush_command() {
  return [
    'ucms-seo-drop-blacklisted' => [
      'description' => "Drop aliases of blacklisted content types",
    ],
  ];
}

/**
 * Do what you have to do.
 */
function drush_ucms_seo_drop_blacklisted() {

  if (!$types = variable_get('ucms_seo_node_type_blacklist')) {
    drush_print(dt("No node types are blacklisted"));
    return 0;
  }

  db_query(
    "DELETE FROM {ucms_seo_node} WHERE nid IN (SELECT nid FROM {node} WHERE type IN (:types))",
    [':types' => $types]
  );

  db_query(
    "DELETE FROM {ucms_seo_alias} WHERE node_id IN (SELECT nid FROM {node} WHERE type IN (:types))",
    [':types' => $types]
  );
}
