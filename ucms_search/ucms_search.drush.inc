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

  foreach ($indices as $index) {
    $start = time();

    drush_print(dt("Proceeding with @index", ['@index' => $index]));

    if ($doClear) {
      // Explicit confirmation in order to avoid accidental clear operations.
      if (!drush_confirm(dt("Should I clear @index ?", ['@index' => $index]))) {
        drush_log(dt("User cancel switching to next index."), 'warning');
        continue;
      }

      ucms_search_index_clear($index);
      drush_log(dt("@index cleared", ['@index' => $index]), 'success');
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

    $progress = drush_create_progress();
    $progress->setProgress($total, 0);
    do {
      $done = ucms_search_index_dequeue_bulk($index);
      $count += $done;
      $progress->setProgress($total, $count);
    } while ($done);
    $progress->end();

    drush_log(dt("@index indexing took @interval", [
      '@index' => $index,
      '@interval' => format_interval(time() - $start),
    ]), 'status');
  }
}
