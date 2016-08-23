<?php
/**
 * @file
 * Drush integration.
 */

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
    'ucms-search-index-attachment' => [
      'description' => "Index awaiting attachments",
      'aliases'     => ['us-attach'],
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
  foreach (ucms_search_index()->names() as $index => $name) {
    $rows[] = [$index, $name];
  }
  drush_print_table($rows, true);
}

/**
 * Reindex.
 */
function drush_ucms_search_index_run() {

  $existing = ucms_search_index()->names();
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

      ucms_search_index()->clear($index);
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
    if ((int)$total <= 0) {
      continue;
    }

    $progress = drush_create_progress();
    $progress->setProgress($total, 0);
    do {
      $done = ucms_search_index()->indexer($index)->bulkDequeue();
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

function drush_ucms_search_index_attachment() {
  $indices = array_keys(ucms_search_index()->names());

  foreach ($indices as $index) {

    $total = (int)db_select('ucms_search_status', 's')
      ->fields('s', ['nid'])
      ->condition('s.index_key', $index)
      ->condition('s.needs_attachments_reindex', 1)
      ->countQuery()
      ->execute()
      ->fetchField()
    ;

    if ($total <= 0) {
      continue;
    }

    $progress = drush_create_progress();
    $progress->setProgress($total, 0);

    $count = 0;
    do {
      $done = \Drupal::service('ucms_search.attachment_indexer')->bulkAttachmentDequeue();
      $count += $done;
      $progress->setProgress($total, $count);
    } while ($done);

    $progress->end();
    drush_log(dt("@index index attachments proceed.", ['@index' => $index]), 'status');
  }
}
