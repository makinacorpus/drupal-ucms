<?php
/**
 * @file
 * Administration screens.
 */

/**
 * Batch operation for index missing.
 */
function ucms_search_admin_index_batch_operation($index, &$context) {

  if (empty($context['sandbox'])) {

    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = db_select('ucms_search_status', 'u')
      ->condition('u.index_key', $index)
      ->condition('u.needs_reindex', 1)
      ->countQuery()
      ->execute()
      ->fetchField()
    ;
    $context['sandbox']['total'] = $context['sandbox']['max'];
    $context['finished'] = 0;
  }

  $done = ucms_search_index_dequeue_bulk($index, 150);
  $context['sandbox']['progress'] += $done;

  if ($done) {
    $context['finished'] = min([$context['sandbox']['progress'] / $context['sandbox']['max'], 0.9999]);
  } else {
    $context['finished'] = 1;
  }
}

/**
 * Batch operation for reindex.
 */
function ucms_search_admin_reindex_batch_operation($index, &$context) {

  if (empty($context['sandbox'])) {

    ucms_search_index_clear($index);

    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = db_select('ucms_search_status', 'u')
      ->condition('u.index_key', $index)
      ->condition('u.needs_reindex', 1)
      ->countQuery()
      ->execute()
      ->fetchField()
    ;
    $context['sandbox']['total'] = $context['sandbox']['max'];
    $context['finished'] = 0;

    // Proceed to next step directly.
    return;
  }

  $done = ucms_search_index_dequeue_bulk($index, 150);
  $context['sandbox']['progress'] += $done;

  if ($done) {
    $context['finished'] = min([$context['sandbox']['progress'] / $context['sandbox']['max'], 0.9999]);
  } else {
    $context['finished'] = 1;
  }
}

