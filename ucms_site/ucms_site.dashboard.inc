<?php
/**
 * @file
 * Dashboard related pages.
 */

use MakinaCorpus\APubSub\CursorInterface;
use MakinaCorpus\APubSub\Error\ChannelDoesNotExistException;
use MakinaCorpus\APubSub\Field;
use MakinaCorpus\Ucms\Dashboard\Page\Page;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Page\SiteAdminDisplay;
use MakinaCorpus\Ucms\Site\Page\WebmasterAdminDisplay;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteState;

/**
 * All sites list page.
 */
function ucms_site_dashboard_site_list_all() {

  $datasource = \Drupal::service('ucms_site.admin.datasource');
  $display    = new SiteAdminDisplay(ucms_site_manager(), t("There is no sites yet."));

  return ucms_dashboard_page_get($datasource, $display, ['dashboard', 'site'])
    ->render(drupal_get_query_parameters(), current_path())
  ;
}

/**
 * My sites list page.
 */
function ucms_site_dashboard_site_list_mine() {
  global $user;

  $datasource = \Drupal::service('ucms_site.admin.datasource');
  $display    = new SiteAdminDisplay(ucms_site_manager(), t("You have no site."));

  return ucms_dashboard_page_get($datasource, $display, ['dashboard', 'site'])
    ->setBaseQuery(['uid' => $user->uid])
    ->render(drupal_get_query_parameters(), current_path())
  ;
}

/**
 * All sites list page.
 */
function ucms_site_dashboard_site_list_archive() {
  global $user;

  $datasource = \Drupal::service('ucms_site.admin.datasource');
  $display    = new SiteAdminDisplay(ucms_site_manager(), t("There is no archives."));
  $page       = ucms_dashboard_page_get($datasource, $display, ['dashboard', 'site']);
  $query      = ['state' => SiteState::ARCHIVE];

  if (!user_access(Access::PERM_SITE_GOD) &&
      !user_access(Access::PERM_SITE_MANAGE_ALLL) &&
      !user_access(Access::PERM_SITE_VIEW_ALL)
  ) {
      $query['uid'] = $user->uid;
  }

  return $page->setBaseQuery($query)
    ->render(drupal_get_query_parameters(), current_path())
  ;
}

/**
 * Site page details
 */
function ucms_site_dashboard_site_page_log(Site $site) {
  try {
    $messages = notification_service_get()
      ->getBackend()
      ->getChannel('site:' . $site->id)
      ->fetch()
    ;
    $messages->addSort(Field::MSG_SENT, CursorInterface::SORT_DESC);

    return notification_block_render_messages($messages);

  } catch (ChannelDoesNotExistException $e) {
    return ['#markup' => '<p>' . t("There is no history to display.") . '<p>'];
  }
}


/**
 * Site's webmasters (and contributors) listing page.
 *
 * @param Site $site
 * @return []
 */
function ucms_site_dashboard_site_page_webmasters(Site $site) {
  $datasource = \Drupal::service('ucms_site.admin.webmaster_datasource');
  $display    = new WebmasterAdminDisplay(ucms_site_manager(), t("There is no webmaster or contributor yet."));

  $page   = ucms_dashboard_page_get($datasource, $display, ['dashboard', 'site', 'webmaster']);
  $query  = ['site_id' => $site->id];

  return $page->setBaseQuery($query)->render(drupal_get_query_parameters(), current_path());
}

