<?php

use MakinaCorpus\Ucms\Contrib\NodeAdminDisplay;
use MakinaCorpus\Ucms\Contrib\PrivateNodeDataSource;
use MakinaCorpus\Ucms\Dashboard\Page\Page;
use MakinaCorpus\Ucms\Search\QueryAlteredSearch;

function ucms_contrib_admin_content_page_mine($tab = null) {
  $search = ucms_search_create('private');
  $search->getFilterQuery()->matchTerm('owner', $GLOBALS['user']->uid);
  return ucms_contrib_admin_content_page($search, $tab);
}

function ucms_contrib_admin_content_page_global($tab = null) {
  $search = ucms_search_create('private');
  $search->getFilterQuery()->matchTerm('is_global', 1);
  return ucms_contrib_admin_content_page($search, $tab);
}

function ucms_contrib_admin_content_page_local($tab = null) {
  $search = ucms_search_create('private');
  $search->getFilterQuery()->matchTerm('is_global', 0);
  return ucms_contrib_admin_content_page($search, $tab);
}

function ucms_contrib_admin_content_page_locked($tab = null) {
  $search = ucms_search_create('private');
  $search->getFilterQuery()->matchTerm('is_locked', 1);
  return ucms_contrib_admin_content_page($search, $tab);
}

function ucms_contrib_admin_content_page_flagged($tab = null) {
  $search = ucms_search_create('private');
  $search->getFilterQuery()->matchTerm('is_flagged', 1);
  return ucms_contrib_admin_content_page($search, $tab);
}

function ucms_contrib_admin_content_page_starred($tab = null) {
  $search = ucms_search_create('private');
  $search->getFilterQuery()->matchTerm('is_starred', 1);
  return ucms_contrib_admin_content_page($search, $tab);
}

/**
 * Main content page.
 *
 * @param string $tab
 *   Tab name.
 */
function ucms_contrib_admin_content_page(QueryAlteredSearch $search, $tab = null) {

  $ret = [];

  $types = variable_get('ucms_contrib_tab_' . $tab . '_type');
  if (!empty($types)) {
    $search->getFilterQuery()->matchTermCollection('type', $types);
  }

  $datasource = new PrivateNodeDataSource($search);
  $display = new NodeAdminDisplay();

  $ret['page'] = ucms_dashboard_page_get($datasource, $display, ['dashboard', 'content'])
    ->render(drupal_get_query_parameters(), current_path())
  ;

  return $ret;
}
