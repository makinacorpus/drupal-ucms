<?php
/**
 * @file
 * Administration screens.
 */

/**
 * Indices management.
 */
function ucms_search_admin_index_form($form, &$form_state) {

  $form['indices'] = [
    '#title'    => t("Select one or more indices to operate"),
    '#type'     => 'checkboxes',
    '#options'  => [
      UCMS_SEARCH_INDEX_PUBLIC  => t("Front-office search index"),
      UCMS_SEARCH_INDEX_BACK    => t("Back-office content administration page index"),
    ],
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

    ucms_search_index_clear($index, true);

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

  ucms_search_cron_index_dequeue($index);

  $remaining = db_select('ucms_search_status', 'u')
    ->condition('u.index_key', $index)
    ->condition('u.needs_reindex', 1)
    ->countQuery()
    ->execute()
    ->fetchField()
  ;

  $context['sandbox']['progress'] = $context['sandbox']['max'] - $remaining;

  if ($remaining) {
    $context['finished'] = min([$context['sandbox']['progress'] / $context['sandbox']['max'], 0.9999]);
  } else {
    $context['finished'] = 1;
  }
}
