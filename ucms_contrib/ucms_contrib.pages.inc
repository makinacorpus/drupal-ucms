<?php

use MakinaCorpus\Ucms\Contrib\PrivateNodeDataSource;
use MakinaCorpus\Ucms\Dashboard\Page\Page;
use MakinaCorpus\Ucms\Dashboard\Page\PageBuilder;

function ucms_contrib_admin_content_page_mine($tab = null) {
  /* @var $datasource PrivateNodeDataSource */
  $datasource = \Drupal::service('ucms_contrib.datasource.elastic');
  $search = $datasource->getSearch();
  $search->getFilterQuery()->matchTerm('owner', $GLOBALS['user']->uid);
  return ucms_contrib_admin_content_page($datasource, $tab);
}

function ucms_contrib_admin_content_page_global($tab = null) {
  /* @var $datasource PrivateNodeDataSource */
  $datasource = \Drupal::service('ucms_contrib.datasource.elastic');
  $search = $datasource->getSearch();
  $search
    ->getFilterQuery()
    ->matchTerm('is_global', 1)
    ->matchTerm('is_group', 0);

  return ucms_contrib_admin_content_page($datasource, $tab);
}

function ucms_contrib_admin_content_page_group($tab = null) {
  /* @var $datasource PrivateNodeDataSource */
  $datasource = \Drupal::service('ucms_contrib.datasource.elastic');
  $search = $datasource->getSearch();
  $search
    ->getFilterQuery()
    ->matchTerm('is_group', 1);
  return ucms_contrib_admin_content_page($datasource, $tab);
}

function ucms_contrib_admin_content_page_local($tab = null) {
  /* @var $datasource PrivateNodeDataSource */
  $datasource = \Drupal::service('ucms_contrib.datasource.elastic');
  $search = $datasource->getSearch();
  $search->getFilterQuery()->matchTerm('is_global', 0);
  return ucms_contrib_admin_content_page($datasource, $tab);
}

function ucms_contrib_admin_content_page_flagged($tab = null) {
  /* @var $datasource PrivateNodeDataSource */
  $datasource = \Drupal::service('ucms_contrib.datasource.elastic');
  $search = $datasource->getSearch();
  $search->getFilterQuery()->matchTerm('is_flagged', 1);
  return ucms_contrib_admin_content_page($datasource, $tab);
}

function ucms_contrib_admin_content_page_starred($tab = null) {
  /* @var $datasource PrivateNodeDataSource */
  $datasource = \Drupal::service('ucms_contrib.datasource.elastic');
  $search = $datasource->getSearch();
  $search->getFilterQuery()->matchTerm('is_starred', 1);
  return ucms_contrib_admin_content_page($datasource, $tab);
}

/**
 * Main content page.
 *
 * @param string $tab
 *   Tab name.
 */
function ucms_contrib_admin_content_page(PrivateNodeDataSource $datasource, $tab = null) {

  $search = $datasource->getSearch();

  // Apply context, if any
  $siteManager = ucms_site_manager();
  if ($siteManager->hasContext()) {
    $search->getFilterQuery()->matchTerm('site_id', $siteManager->getContext()->getId());
  }

  $types = ucms_contrib_type_handler_get()->getTabTypes($tab);
  if (!empty($types)) {
    $search->getFilterQuery()->matchTermCollection('type', $types);
  }

  /** @var \MakinaCorpus\Ucms\Dashboard\Page\PageBuilder $builder */
  $builder = \Drupal::service('ucms_dashboard.page_builder');
  $result = $builder->search($datasource, \Drupal::requestStack()->getCurrentRequest());

  return $builder->render($result);
}
