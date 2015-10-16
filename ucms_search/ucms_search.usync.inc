<?php
/**
 * @file
 * µSync module integration.
 */

/**
 * Implements hook_usync_loader_list().
 */
function ucms_search_usync_loader_list() {
  return [
    new \Ucms\Search\USync\IndexLoader(),
  ];
}

/**
 * Implements usync_path_map().
 */
function ucms_search_usync_path_map() {
  return [
    'ucms.search.index.?name' => '\Ucms\Search\USync\IndexNode',
  ];
}
