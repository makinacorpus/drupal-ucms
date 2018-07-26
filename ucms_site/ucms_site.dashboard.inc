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

