<?php
/**
 * @file
 * Administration screens.
 */

/**
 * FIXME move this elsewhere.
 *
 * Answer taken from https://stackoverflow.com/a/2510459 - all credits to its
 * original author from the http://php.net website.
 */
function ucms_search_admin_index_format_bytes($bytes, $precision = 2) {
  $units = ['B', 'KB', 'MB', 'GB', 'TB'];

  $bytes  = max($bytes, 0);
  $pow    = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow    = min($pow, count($units) - 1);

  // Uncomment one of the following alternatives
  $bytes /= pow(1024, $pow);
  // $bytes /= (1 << (10 * $pow));

  return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Indices management.
 */
function ucms_search_admin_index_form($form, &$form_state) {

  $header = [
    t("Id"),
    t("Name"),
    t("Total"),
    t("Queued"),
    t("Indexed"),
    t("Size"),
    ''
  ];

  $client   = ucms_search_client_get();
  $indices  = ucms_search_index_list();
  $stats    = null;

  $q = db_select('ucms_search_status', 's');
  $q->fields('s', ['index_key']);
  $q->addExpression("COUNT(index_key)", 'count');
  $waiting = $q
    ->condition('s.needs_reindex', 0, '<>')
    ->groupBy('s.index_key')
    ->execute()
    ->fetchAllKeyed()
  ;
  $q = db_select('ucms_search_status', 's');
  $q->fields('s', ['index_key']);
  $q->addExpression("COUNT(index_key)", 'count');
  $total = $q
    ->groupBy('s.index_key')
    ->execute()
    ->fetchAllKeyed()
  ;

  try {
    $stats = $client->indices()->status(['index' => implode(',', array_keys($indices))]);
  } catch (Exception $e) {
    watchdog_exception(__FUNCTION__, $e);
  }

  $rows= [];
  foreach ($indices as $index => $name) {
    $row = [
      check_plain($index),
      check_plain($name),
    ];

    $row[] = isset($total[$index]) ? number_format($total[$index]) : 0;
    $row[] = isset($waiting[$index]) ? number_format($waiting[$index]) : 0;

    if ($stats && $stats['indices'][$index]) {
      $row[] = number_format($stats['indices'][$index]['docs']['num_docs']);
      $row[] = ucms_search_admin_index_format_bytes($stats['indices'][$index]['index']['size_in_bytes']);
    } else {
      $row[] = $row[] = '<em>' . t("Error") . '</em>';
    }

    $row[] = ''; // @todo Operations.

    $rows[$index] = $row;
  }

  $form['indices'] = [
    '#type'     => 'tableselect',
    '#header'   => $header,
    '#options'  => $rows,
    '#required' => true,
  ];

  $form['actions']['#type'] = 'actions';
  $form['actions']['reindex'] = [
    '#type'   => 'submit',
    '#value'  => t("Re-index selected"),
    '#submit' => ['ucms_search_admin_index_form_reindex_submit'],
  ];

  return $form;
}

/**
 * Indices management re-index button submit.
 */
function ucms_search_admin_index_form_reindex_submit($form, &$form_state) {

  $operations = [];
  foreach ($form_state['values']['indices'] as $index => $value) {
    if ($value && $value === $index) {
      $operations[] = ['ucms_search_admin_index_batch_operation', [$index]];
    }
  }

  batch_set([
    'title'       => t("Re-indexing"),
    'file'        => drupal_get_path('module', 'ucms_search') . '/ucms_search.admin.inc',
    'operations'  => $operations,
  ]);
}

/**
 * Batch operation for reindex.
 */
function ucms_search_admin_index_batch_operation($index, &$context) {

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
