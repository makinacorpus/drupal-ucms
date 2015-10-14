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

  $param = \Ucms\Search\QueryAlteredSearch::PARAM_FULLTEXT_QUERY;

  $form['query'] = [
    '#type'           => 'textfield',
    '#attributes'     => ['placeholder' => t("Search")],
    '#default_value'  => isset($_GET[$param]) ? $_GET[$param] : null,
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
  $param = \Ucms\Search\QueryAlteredSearch::PARAM_FULLTEXT_QUERY;
  if (!empty($form_state['values']['query'])) {
    $query = [$param => $form_state['values']['query']];
    $form_state['redirect'] = [
      current_path(),
      ['query' => $query + drupal_get_query_parameters()],
    ];
  } else {
    $form_state['redirect'] = [
      current_path(),
      ['query' => drupal_get_query_parameters(null, ['q', $param])],
    ];
  }
}

/**
 * Main content page.
 *
 * @param string $tab
 *   Tab name.
 */
function ucms_contrib_admin_content_page($tab = null) {

  $ret = [
    '#theme'    => 'ucms_contrib_content_page',
    '#facets'   => [],
    '#search'   => drupal_get_form('ucms_contrib_admin_content_search_form'),
  ];

  _ucms_contrib_render_attach($ret);

  if (user_access('use favorites')) {
    $ret['#favorites'] = ucms_contrib_favorite_render();
  }

  $search = ucms_search_create(UCMS_SEARCH_INDEX_BACK);

  // Build facets
  $ret['#facets']['type'] = $search
    ->createTermAggregation('type', null)
    ->setChoicesMap(node_type_get_names())
    ->setTitle(t("Type"))
  ;

  $ret['#facets']['owner'] = $search
    ->createTermAggregation('owner', null)
    ->setChoicesCallback(function ($values) {
      if ($accounts = user_load_multiple($values)) {
        foreach ($accounts as $index => $account) {
          $accounts[$index] = format_username($account);
        }
        return $accounts;
      }
    })
    ->setTitle(t("Owner"))
  ;

  $ret['#facets']['tags'] = $search
    ->createTermAggregation('tags', null)
    ->setChoicesCallback(function ($values) {
      if ($terms = taxonomy_term_load_multiple($values)) {
        foreach ($terms as $index => $term) {
          $terms[$index] = check_plain($term->name);
        }
        return $terms;
      }
    })
    ->setTitle(t("Tags"))
  ;

  $ret['#facets']['status'] = $search
    ->createTermAggregation('status', null)
    ->setChoicesMap([0 => t("Unpublished"), 1 => t("Published")])
    ->setTitle(t("Status"))
  ;

  $types = variable_get('ucms_contrib_tab_' . $tab . '_type');
  if (!empty($types)) {
    $search->getFilterQuery()->matchTermCollection('type', $types);
  }

  $limit = 24;

  $response = $search
    ->addField('_id')
    ->setLimit($limit)
    ->prepare($_GET)
    ->doSearch()
  ;

  pager_default_initialize($response->getTotal(), $limit);

  if ($nidList = $response->getAllNodeIdentifiers()) {

    $display = (new \Ucms\Contrib\ContentAdminListDisplay())
      ->prepareFromQuery($_GET)
    ;

    $ret['#display']  = $display->renderLinks();
    $ret['#nodes']    = $display->render(node_load_multiple($nidList));
    $ret['#pager']    = theme('pager');
  }

  return $ret;
}
