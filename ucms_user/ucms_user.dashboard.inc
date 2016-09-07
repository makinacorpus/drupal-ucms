<?php


use MakinaCorpus\Ucms\User\Page\UserAdminDisplay;


/**
 * Callback of the users listing page.
 */
function ucms_user_dashboard_user_list() {
  $datasource = \Drupal::service('ucms_user.admin.datasource');
  $display    = new UserAdminDisplay(t("There is no users yet."));

  return ucms_dashboard_page_get($datasource, $display, ['dashboard', 'user'])
    ->render(drupal_get_query_parameters(), current_path());
}
