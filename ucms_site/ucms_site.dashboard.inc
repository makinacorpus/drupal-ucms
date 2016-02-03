<?php
/**
 * @file
 * Dashboard related pages.
 */

use MakinaCorpus\Ucms\Dashboard\Page\Page;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Page\SiteAdminDisplay;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteState;

/**
 * All sites list page.
 */
function ucms_site_dashboard_site_list_all() {

  $datasource = \Drupal::service('ucms_site.admin.datasource');
  $display    = new SiteAdminDisplay(t("There is not sites yet."));

  return ucms_dashboard_page_get($datasource, $display, ['dashboard', 'site'])->render();
}

/**
 * My sites list page.
 */
function ucms_site_dashboard_site_list_mine() {
  global $user;

  $datasource = \Drupal::service('ucms_site.admin.datasource');
  $display    = new SiteAdminDisplay(t("You have not site."));

  return ucms_dashboard_page_get($datasource, $display, ['dashboard', 'site'])
    ->setBaseQuery(['uid' => $user->uid])
    ->render()
  ;
}

/**
 * All sites list page.
 */
function ucms_site_dashboard_site_list_archive() {
  global $user;

  $datasource = \Drupal::service('ucms_site.admin.datasource');
  $display    = new SiteAdminDisplay(t("There is no archives."));
  $page       = ucms_dashboard_page_get($datasource, $display, ['dashboard', 'site']);
  $query      = ['state' => SiteState::ARCHIVE];

  if (!user_access(Access::PERM_SITE_VIEW_ALL)) {
      $query['uid'] = $user->uid;
  }

  return $page->setBaseQuery($query)->render();
}
