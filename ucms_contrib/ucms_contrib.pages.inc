<?php

/**
 * Node add to favorites form.
 */
function ucms_contrib_favorite_add_form($form, &$form_state, $nid) {
  $form['#nid'] = $nid;
  $form['#uid'] = $GLOBALS['user']->uid;
  $form['submit'] = [
    '#type'   => 'submit',
    '#value'  => t("Add to favorites"),
  ];
  return $form;
}

/**
 * Node add to favorites form submit.
 */
function ucms_contrib_favorite_add_form_submit($form, &$form_state) {
  $node = node_load($form['#nid']);
  if (ucms_contrib_favorite_add($form['#nid'], $form['#uid'])) {
    drupal_set_message(t("%title added to your favorites", ['%title' => $node->title]));
  } else {
    drupal_set_message(t("%title is already a favorite", ['%title' => $node->title]), 'warning');
  }
}

/**
 * Remove node from favorites form.
 */
function ucms_contrib_favorite_remove_form($form, &$form_state, $nid) {
  $form['#nid'] = $nid;
  $form['#uid'] = $GLOBALS['user']->uid;
  $form['submit'] = [
    '#type'   => 'submit',
    '#value'  => t("Remove from favorites"),
  ];
  return $form;
}

/**
 * Node remove from favorites form submit.
 */
function ucms_contrib_favorite_remove_form_submit($form, &$form_state) {
  $node = node_load($form['#nid']);
  ucms_contrib_favorite_remove($form['#nid'], $form['#uid']);
  drupal_set_message(t("%title removed from your favorites", ['%title' => $node->title]));
}

/**
 * Search form.
 */
function ucms_contrib_admin_content_search_form($form, &$form_state) {

  $form['query'] = [
    '#type'           => 'textfield',
    '#attributes'     => ['placeholder' => t("Search")],
    '#default_value'  => isset($_GET['s']) ? $_GET['s'] : null,
  ];

  $form['submit'] = [
    '#type'   => 'submit',
    '#value'  => t("Search"),
  ];

  return $form;
}

/**
 * Search form submit.
 */
function ucms_contrib_admin_content_search_form_submit($form, &$form_state) {
  if (!empty($form_state['values']['query'])) {
    $query = ['s' => $form_state['values']['query']];
    $form_state['redirect'] = [
      current_path(),
      ['query' => drupal_get_query_parameters($query)],
    ];
  } else {
    $form_state['redirect'] = current_path();
  }
}

/**
 * Main content page.
 *
 * @param string[] $types
 *   Content types (UCMS_CONTENT_TYPE_* constants list).
 */
function ucms_contrib_admin_content_page($types = null) {

  /*
   * FIXME Testing, REMOVE ME !
  ucms_search_index_reindex(UCMS_SEARCH_INDEX_PUBLIC);
  ucms_search_index_reindex(UCMS_SEARCH_INDEX_BACK);
   */

  $limit  = 16;
  $page   = isset($_GET['page']) ? ($_GET['page'] + 1) : 1;

  $ret = [
    '#theme'    => 'ucms_contrib_content_page',
    '#attached' => [
      'css' => [
        drupal_get_path('module', 'ucms_contrib') . '/ucms_contrib.css',
      ],
      'js' => [
        drupal_get_path('module', 'ucms_contrib') . '/ucms_contrib.js',
      ],
    ],
    '#items'    => [],
    '#search'   => drupal_get_form('ucms_contrib_admin_content_search_form'),
  ];

  if (user_access('use favorites')) {
    $ret['#favorites'] = ucms_contrib_favorite_render();
  }

  $search = ucms_search_create(UCMS_SEARCH_INDEX_BACK);
  $typeFacet = $search
    ->createTermAggregation('type', null)
    ->setChoicesMap(
      node_type_get_names()
    )
  ;

  if ($types !== null) {
    $search
      ->getFilterQuery()
      ->matchTermCollection('type', ucms_contrib_type_to_list($types))
    ;
  }

  if (!empty($_GET['s'])) {
    $search
      ->getQuery()
      ->matchTerm('combined', $_GET['s'], null, 0.8)
    ;
  }

  $response = $search
    ->setLimit($limit)
    ->setPage($page)
    ->addField('_id')
    ->doSearch()
  ;

  pager_default_initialize($response->getTotal(), $limit);

  if ($nidList = $response->getAllNodeIdentifiers()) {
    $view = node_view_multiple(node_load_multiple($nidList), UCMS_VIEW_MODE_CONTENTADMIN);
    foreach (element_children($view['nodes']) as $nid) {
      $ret['#items'][$nid] = $view['nodes'][$nid];
      if (user_access('use favorites')) {
        $ret['#items'][$nid]['add'] = drupal_get_form('ucms_contrib_favorite_add_form_' . $nid, $nid);
      }
    }
  }

  $ret['#pager'] = theme('pager');

  return $ret;
}
