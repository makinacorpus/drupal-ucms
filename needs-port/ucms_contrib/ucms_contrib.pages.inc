<?php

use MakinaCorpus\Ucms\Contrib\NodeAdminDisplay;
use MakinaCorpus\Ucms\Contrib\PrivateNodeDataSource;

function ucms_contrib_admin_content_page_mine($tab = null) {
  /** @var \MakinaCorpus\Ucms\Contrib\PrivateNodeDataSource $datasource */
  $datasource = \Drupal::service('ucms_contrib.datasource.elastic');
  $search = $datasource->getSearch();
  $search->getFilterQuery()->matchTerm('owner', $GLOBALS['user']->uid);
  return ucms_contrib_admin_content_page($datasource, $tab);
}

function ucms_contrib_admin_content_page_global($tab = null) {
  /** @var \MakinaCorpus\Ucms\Contrib\PrivateNodeDataSource $datasource */
  $datasource = \Drupal::service('ucms_contrib.datasource.elastic');
  $search = $datasource->getSearch();
  $search
    ->getFilterQuery()
    ->matchTerm('is_global', 1)
    ->matchTerm('is_group', 0);

  return ucms_contrib_admin_content_page($datasource, $tab);
}

function ucms_contrib_admin_content_page_local($tab = null) {
  /** @var \MakinaCorpus\Ucms\Contrib\PrivateNodeDataSource $datasource */
  $datasource = \Drupal::service('ucms_contrib.datasource.elastic');
  $search = $datasource->getSearch();
  $search->getFilterQuery()->matchTerm('is_global', 0);

  // Apply context, if any
  $siteManager = ucms_site_manager();
  if ($siteManager->hasContext()) {
    $search->getFilterQuery()->matchTerm('site_id', $siteManager->getContext()->getId());
  }

  return ucms_contrib_admin_content_page($datasource, $tab);
}

/**
 * Main content page.
 *
 * @param \MakinaCorpus\Ucms\Contrib\PrivateNodeDataSource $datasource
 * @param string $tab Tab name.
 * @return array
 */
function ucms_contrib_admin_content_page(PrivateNodeDataSource $datasource, $tab = null) {

  $ret = [];
  $search = $datasource->getSearch();

  $types = ucms_contrib_type_handler_get()->getTabTypes($tab);
  if (!empty($types)) {
    $search->getFilterQuery()->matchTermCollection('type', $types);
  }

  $display = new NodeAdminDisplay();

  $ret['page'] = ucms_dashboard_page_get($datasource, $display, ['dashboard', 'content'])
    ->render(drupal_get_query_parameters(), current_path())
  ;

  return $ret;
}