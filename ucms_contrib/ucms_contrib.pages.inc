<?php

use MakinaCorpus\Ucms\Contrib\NodeAdminDisplay;
use MakinaCorpus\Ucms\Contrib\PrivateNodeDataSource;
use MakinaCorpus\Ucms\Dashboard\Page\Page;

/**
 * Main content page.
 *
 * @param string $tab
 *   Tab name.
 */
function ucms_contrib_admin_content_page($tab = null) {

  $ret = [];

  $search = ucms_search_create(UCMS_SEARCH_INDEX_BACK);

  $types = variable_get('ucms_contrib_tab_' . $tab . '_type');
  if (!empty($types)) {
    $search->getFilterQuery()->matchTermCollection('type', $types);
  }

  $datasource = new PrivateNodeDataSource($search);
  $display = new NodeAdminDisplay();

  $ret['page'] = ucms_dashboard_page_get($datasource, $display, ['dashboard', 'content'])
    ->render(drupal_get_query_parameters(), current_path()
  ;

  return $ret;
}
