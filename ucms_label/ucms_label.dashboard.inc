<?php
/**
 * @file
 * Dashboard related pages.
 */


use MakinaCorpus\APubSub\Error\ChannelDoesNotExistException;
use MakinaCorpus\Ucms\Dashboard\Page\Page;
use MakinaCorpus\Ucms\Label\LabelAccess;
use MakinaCorpus\Ucms\Label\Page\LabelAdminDisplay;


/**
 * Labels list page.
 */
function ucms_label_dashboard_label_list() {
  $datasource = \Drupal::service('ucms_label.admin.datasource');
  $display    = new LabelAdminDisplay(t("There is not labels yet."));

  return ucms_dashboard_page_get($datasource, $display, ['dashboard', 'label'])
    ->render(drupal_get_query_parameters(), current_path());
}


/**
 * Confirmation form for label removal.
 */
//function ucms_label_dashboard_label_delete_form($form, &$form_state) {
//
//}


/**
 *
 * @param stdClass $label
 * @return []
 */
//function ucms_label_dashboard_label_log($label) {
//
//  try {
//    $messages = notification_service_get()
//      ->getBackend()
//      ->getChannel('site:' . $site->id)
//      ->fetch();
//
//    notification_block_render_messages($messages);
//  }
//  catch (ChannelDoesNotExistException $e) {
//    return ['#markup' => '<p>' . t("There is no history to display.") . '<p>'];
//  }
//
//  return [];
//}
