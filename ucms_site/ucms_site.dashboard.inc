<?php
/**
 * Dashboard related pages.
 *
 * @todo
 *   All functions in here belong to controllers
 */

use MakinaCorpus\APubSub\CursorInterface;
use MakinaCorpus\APubSub\Error\ChannelDoesNotExistException;
use MakinaCorpus\APubSub\Field;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteState;

/**
 * All sites list page.
 *
 * @deprecated
 *   Convert me to controller
 */
function ucms_site_dashboard_site_list_all() {

  $datasource = \Drupal::service('ucms_site.admin.datasource');
  /** @var \MakinaCorpus\Drupal\Dashboard\AdminWidgetFactory $factory */
  $factory    = \Drupal::service('udashboard.admin_widget_factory');

  return $factory
      ->createPageBuilder()
      ->setDatasource($datasource)
      ->setBaseQuery([])
      ->setAllowedTemplates(['table' => 'module:ucms_site:Page/page-site-admin.html.twig'])
      ->searchAndRender(\Drupal::request())
  ;
}

/**
 * My sites list page.
 *
 * @deprecated
 *   Convert me to controller
 */
function ucms_site_dashboard_site_list_mine() {
  global $user;

  $datasource = \Drupal::service('ucms_site.admin.datasource');
  /** @var \MakinaCorpus\Drupal\Dashboard\AdminWidgetFactory $factory */
  $factory    = \Drupal::service('udashboard.admin_widget_factory');

  return $factory
      ->createPageBuilder()
      ->setDatasource($datasource)
      ->setBaseQuery(['uid' => $user->uid])
      ->setAllowedTemplates(['table' => 'module:ucms_site:Page/page-site-admin.html.twig'])
      ->searchAndRender(\Drupal::request())
  ;
}

/**
 * All sites list page.
 *
 * @deprecated
 *   Convert me to controller
 */
function ucms_site_dashboard_site_list_archive() {
  global $user;

  $datasource = \Drupal::service('ucms_site.admin.datasource');
  $query      = ['state' => SiteState::ARCHIVE];

  if (!user_access(Access::PERM_SITE_GOD) &&
      !user_access(Access::PERM_SITE_MANAGE_ALL) &&
      !user_access(Access::PERM_SITE_VIEW_ALL)
  ) {
      $query['uid'] = $user->uid;
  }

  /** @var \MakinaCorpus\Drupal\Dashboard\AdminWidgetFactory $factory */
  $factory = \Drupal::service('udashboard.admin_widget_factory');

  return $factory
      ->createPageBuilder()
      ->setDatasource($datasource)
      ->setBaseQuery($query)
      ->setAllowedTemplates(['table' => 'module:ucms_site:Page/page-site-admin.html.twig'])
      ->searchAndRender(\Drupal::request())
  ;
}

/**
 * Site page details
 *
 * @deprecated
 *   Convert me to controller
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
 *
 * @deprecated
 *   Convert me to controller
 */
function ucms_site_dashboard_site_page_webmasters(Site $site) {

  $datasource = \Drupal::service('ucms_site.admin.webmaster_datasource');
  /** @var \MakinaCorpus\Drupal\Dashboard\AdminWidgetFactory $factory */
  $factory = \Drupal::service('udashboard.admin_widget_factory');

  return $factory
      ->createPageBuilder()
      ->setDatasource($datasource)
      ->setBaseQuery(['site_id' => $site->getId()])
      ->setAllowedTemplates(['table' => 'module:ucms_site:Page/page-site-users.html.twig'])
      ->searchAndRender(\Drupal::request())
  ;
}

