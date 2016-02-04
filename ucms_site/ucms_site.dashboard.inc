<?php
/**
 * @file
 * Dashboard related pages.
 */

use MakinaCorpus\APubSub\Error\ChannelDoesNotExistException;
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
  $display    = new SiteAdminDisplay(t("You have not site."));

  return ucms_dashboard_page_get($datasource, $display, ['dashboard', 'site'])
    ->setBaseQuery(['uid' => $user->uid])
    ->render(drupal_get_query_parameters(), current_path()
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

  return $page->setBaseQuery($query)
    ->render(drupal_get_query_parameters(), current_path()
  ;
}

/**
 * Site page details
 */
function ucms_site_dashboard_site_page_view(Site $site) {

  $ret = [];

  $requester = user_load($site->uid);
  if (!$requester) {
    $requester = drupal_anonymous_user();
  }

  $template = null;
  if ($site->template_id) {
    try {
      $template = ucms_site_manager()->getStorage()->findOne($site->template_id);
      $template = l($template->title, 'admin/dashboard/site/' . $template->id);
    } catch (\Exception $e) {
      $template = '<span class="text-muted>' . t("Template does not exist anymore") . '</span>';
    }
  } else {
    $template = '';
  }

  $states = SiteState::getList();

  $uri = 'http://' . $site->http_host;

  $rows = [
      [['data' => '<strong>' . t("Identification") . '</strong>', 'colspan' => 2]],
      [t("HTTP hostname"), l($uri, $uri)],
      [t("State"), t($states[$site->state])],
      [t("Title"), check_plain($site->title)],
      [t("Created at"), format_date($site->ts_created->getTimestamp())],
      [t("Lastest update"), format_date($site->ts_changed->getTimestamp())],
      [t("Requester"), format_username($requester)],
      [['data' => '<strong>' . t("Description") . '</strong>', 'colspan' => 2]],
      [t("Description"), check_markup($site->title_admin)],
      [t("Replaces"), check_markup($site->replacement_of)],
      [t("HTTP redirections"), check_markup($site->http_redirects)],
      [['data' => '<strong>' . t("Display information") . '</strong>', 'colspan' => 2]],
      [t("Is a template"), $site->is_template ? '<strong>' . t("yes") . '</strong>' : t("No")],
      [t("Site template"), $template],
      [t("Theme"), check_plain($site->theme)],
  ];

  $ret[] = [
      '#theme'    => 'table__ucms_site_details',
      '#rows'     => $rows,
  ];

  return $ret;
}

function ucms_site_dashboard_site_page_log(Site $site) {

  try {

    $messages = notification_service_get()
      ->getBackend()
      ->getChannel('site:' . $site->id)
      ->fetch()
    ;

    notification_block_render_messages($messages);

  } catch (ChannelDoesNotExistException $e) {
    return ['#markup' => '<p>' . t("There is no history to display.") . '<p>'];
  }

  return [];
}

function ucms_site_dashboard_site_page_webmasters(Site $site) {
  return [];
}
