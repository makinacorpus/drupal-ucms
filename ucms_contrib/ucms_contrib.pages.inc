<?php

use MakinaCorpus\Ucms\Contrib\NodeAdminDisplay;
use MakinaCorpus\Ucms\Contrib\PrivateNodeDataSource;
use MakinaCorpus\Ucms\Dashboard\Page\Page;

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
 * Main content page.
 *
 * @param string $tab
 *   Tab name.
 */
function ucms_contrib_admin_content_page($tab = null) {

  $ret = [];

  _ucms_contrib_render_attach($ret);

  $search = ucms_search_create(UCMS_SEARCH_INDEX_BACK);

  $types = variable_get('ucms_contrib_tab_' . $tab . '_type');
  if (!empty($types)) {
    $search->getFilterQuery()->matchTermCollection('type', $types);
  }

  $datasource = new PrivateNodeDataSource($search, new NodeAdminDisplay());

  $ret['page'] = (new Page($datasource, ['dashboard', 'content']))->render();

  if (user_access('use favorites')) {
    $ret['favorites'] = ucms_contrib_favorite_render();
  }

  return $ret;
}
