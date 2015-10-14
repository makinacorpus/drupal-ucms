<?php
/**
 * @file
 * Drush integration.
 */

@include_once __DIR__ . '/progress.drush.inc';

/**
 * Implements hook_drush_command().
 */
function ucms_search_drush_command() {
  return [
    'ucms-search-index-run' => [
      'description' => "Index whatever should be indexed",
      'options'     => [
        'clear'     => "Clear index before indexing content (reindexes everything)",
        'index'     => "Indices to reindex, use a comma separated list of server keys",
      ],
      'aliases'     => ['us-index'],
    ],
    'ucms-search-index-list' => [
      'description' => "Reindex everything",
      'aliases'     => ['us-list'],
    ],
  ];
}

/**
 * List all indices.
 */
function drush_ucms_search_index_list() {
  $rows = [
    [dt("Module"), dt("Source")]
  ];
  foreach (ucms_search_index_list() as $index => $name) {
    $rows[] = [$index, $name];
  }
  drush_print_table($rows, true);
}

/**
 * Reindex.
 */
function drush_ucms_search_index_run() {

  $existing = ucms_search_index_list();
  $indices  = [];
  $doClear  = drush_get_option('clear');

  if ($indices = drush_get_option('index')) {
    $indices = explode(',', $indices);

    if (!$indices) {
      drush_print(dt("Invalid index value"));
      drush_die();
    }

    $missing = array_diff($indices, array_keys($existing));
    if ($missing) {
      drush_print(dt("Unknown indices: @indices", ['@indices' => implode(', ', $missing)]));
      drush_die();
    }
  }

  if (!$indices) {
    $indices = array_keys($existing);
  }

  drush_print(dt("Will reindex: @indices", ['@indices' => implode(', ', $indices)]));
  if (!drush_confirm(dt("Do you want to proceed ?"))) {
    drush_user_abort();
    return;
  }

  $limit = UCMS_SEARCH_CRON_INDEX_LIMIT;

  foreach ($indices as $index) {
    $start = time();

    drush_print(dt("Proceeding with @index", ['@index' => $index]));

    if ($doClear) {
      // Explicit confirmation in order to avoid accidental clear operations.
      drush_print(dt("@index will be cleared", ['@index' => $index]));
      if (!drush_confirm(dt(" - Do you want to proceed ?"))) {
        drush_print(dt(" ! User cancel switching to next index."));
        continue;
      }

      ucms_search_index_clear($index);
      drush_print(' - ' . dt("node status cleared"));
    }

    $count = 0;
    $total = db_select('ucms_search_status', 's')
      ->fields('s', ['nid'])
      ->condition('s.needs_reindex', 1)
      ->condition('s.index_key', $index)
      ->countQuery()
      ->execute()
      ->fetchField()
    ;

    $nodeIdList   = [];
    $progressBar  = drush_create_progress();

    do {

      $nodeIdList = db_select('ucms_search_status', 's')
        ->fields('s', ['nid'])
        ->condition('s.needs_reindex', 1)
        ->condition('s.index_key', $index)
        ->range(0, $limit)
        ->execute()
        ->fetchCol()
      ;

      $count += count($nodeIdList);

      // Preload all nodes for performance.
      $nodes = node_load_multiple($nodeIdList);
      ucms_search_index_node_index_bulk($index, $nodes, true, false);

      $progressBar->setProgress($total, $count);

      /*
      $toBeDeleted = array_diff(array_keys($nodes), $nodeIdList);
      foreach ($toBeDeleted as $nid) {
        ucms_search_index_node_delete($index, $nid);
      }
       */
    } while ($nodeIdList);

    $progressBar->end();

    drush_print(" - " . dt("processing took @interval", ['@interval' => format_interval(time() - $start)]));
  }
}
