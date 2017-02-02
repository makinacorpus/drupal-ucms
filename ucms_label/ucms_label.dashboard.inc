<?php
/**
 * @file
 * Dashboard related pages.
 */


use MakinaCorpus\Ucms\Label\Page\LabelAdminDisplay;


/**
 * Labels list page.
 */
function ucms_label_dashboard_label_list() {
  $datasource = \Drupal::service('ucms_label.admin.datasource');
  $display    = new LabelAdminDisplay(t("There is not labels yet."));

  return udashboard_page_get($datasource, $display, ['dashboard', 'label'])
    ->render(drupal_get_query_parameters(), current_path());
}
