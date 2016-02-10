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
function ucms_label_dashboard_label_delete_form($form, &$form_state, $label) {
//  $manager = \Drupal::service('ucms_label.manager');
//
//  if ($manager->hasChildren($label)) {
//    $form['not_allowed'] = [
//      '#prefix' => '<div class="alert alert-info">',
//      '#suffix' => '</div>',
//      'message' => [
//        '#theme' => 'html_tag',
//        '#tag' => 'p',
//        '#value' => t("\"@name\" label has currently children labels. It can't be removed.", ['@name' => $label->name]),
//      ],
//      'back_link' => [
//        '#theme' => 'link',
//        '#text' => t("Return to the labels list"),
//        '#path' => 'admin/dashboard/label',
//        '#options' => ['html' => false, 'attributes' => ['class' => ['alert-link']]],
//      ],
//    ];
//
//    return $form;
//  }

  $form_state['label'] = $label;
  $question = t("Do you really want to delete the \"@name\" label?", ['@name' => $label->name]);
  return confirm_form($form, $question, 'admin/dashboard/label');
}


/**
 * Confirmation callback for the label removal form.
 */
function ucms_label_dashboard_label_delete_form_submit(&$form, &$form_state) {
  $manager = \Drupal::service('ucms_label.manager');

  try {
    $manager->deleteLabel($form_state['label']);
    drupal_set_message(t("\"@name\" label has been deleted.", array('@name' => $form_state['label']->name)));
  }
  catch (Exception $e) {
    drupal_set_message(t("Error during deletion of the \"@name\" label. Please try again.", array('@name' => $form_state['label']->name)), 'error');
  }
}
