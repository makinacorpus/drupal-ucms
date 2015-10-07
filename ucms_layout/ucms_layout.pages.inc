<?php
/**
 * @file
 * Layout management user pages.
 */

/**
 * Layout add form.
 */
function ucms_layout_admin_layout_add_form($form, &$form_state) {

  $form['title'] = [
    '#title'          => t("Page title"),
    '#type'           => 'textfield',
    '#required'       => true,
    '#description'    => t("The page title that will be displayed on frontend."),
  ];

  $form['title_admin'] = [
    '#title'          => t("Title for administration"),
    '#type'           => 'textfield',
    '#required'       => true,
    '#description'    => t("This title will used for administrative pages and will never be shown to end users."),
  ];

  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = [
    '#type'   => 'submit',
    '#value'  => t("Create"),
    '#submit' => ['ucms_layout_admin_layout_add_form_submit'],
  ];

  return $form;
}

/**
 * Layout add form submit.
 */
function ucms_layout_admin_layout_add_form_submit($form, &$form_state) {

  $layout = (new \Ucms\Layout\Layout())
    ->setAccountId($GLOBALS['user']->uid)
    ->setAdminTitle($form_state['values']['title_admin'])
    ->setTitle($form_state['values']['title'])
    ->setSiteId(0) // @todo
  ;

  ucms_layout_save($layout);

  drupal_set_message(t("Page %page has been created.", ['%page' => $layout->getAdminTitle()]));

  $form_state['redirect'] = 'layout/' . $layout->getId();
}

/**
 * Layout edit form.
 */
function ucms_layout_admin_layout_edit_form($form, &$form_state, \Ucms\Layout\Layout $layout) {

  $form['#layout_id'] = $layout->getId();

  $form['title'] = [
    '#title'          => t("Page title"),
    '#type'           => 'textfield',
    '#required'       => true,
    '#default_value'  => $layout->getTitle(),
    '#description'    => t("The page title that will be displayed on frontend."),
  ];

  $form['title_admin'] = [
    '#title'          => t("Title for administration"),
    '#type'           => 'textfield',
    '#required'       => true,
    '#default_value'  => $layout->getAdminTitle(),
    '#description'    => t("This title will used for administrative pages and will never be shown to end users."),
  ];

  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = [
    '#type'   => 'submit',
    '#value'  => t("Update"),
    '#submit' => ['ucms_layout_admin_layout_edit_form_update_submit'],
  ];
  $form['actions']['delete'] = [
    '#type'   => 'submit',
    '#value'  => t("Delete"),
    '#submit' => ['ucms_layout_admin_layout_edit_form_delete_submit'],
    '#limit_validation_errors' => [],
  ];

  return $form;
}

/**
 * Layout edit form update submit.
 */
function ucms_layout_admin_layout_edit_form_update_submit($form, &$form_state) {

  $layout = ucms_layout_load($form['#layout_id'])
    ->setAdminTitle($form_state['values']['title_admin'])
    ->setTitle($form_state['values']['title'])
    ->setSiteId(0) // @todo
  ;

  ucms_layout_save($layout);

  drupal_set_message(t("Page %page has been updated.", ['%page' => $layout->getAdminTitle()]));

  $form_state['redirect'] = 'layout/' . $layout->getId();
}

/**
 * Layout edit form delete submit.
 */
function ucms_layout_admin_layout_edit_form_delete_submit($form, &$form_state) {
  // Else drupal_goto() will force the destination parameter to override us.
  $destination = drupal_get_destination();
  unset($_GET['destination']);
  $form_state['redirect'] = ['layout/' . $form['#layout_id'] . '/delete', ['query' => $destination]];
}

/**
 * Layout delete form.
 */
function ucms_layout_admin_layout_delete_form($form, &$form_state, \Ucms\Layout\Layout $layout) {

  $form['#layout_id'] = $layout->getId();
  $form['#layout_title'] = $layout->getTitle();

  $question = t("Delete");
  $description = t("Remove %page page ?", ['%page' => $layout->getAdminTitle()]);

  return confirm_form($form, $question, 'layout/' . $layout->getId(), $description);
}

/**
 * Used by the delete confirm form.
 */
function ucms_layout_admin_layout_delete_form_submit($form, &$form_state) {

  ucms_layout_delete($form['#layout_id']);

  drupal_set_message(t("Page %page has been deleted.", ['%page' => $form['#layout_title']]), 'error');

  // The layout does not exists anymore, we cannot return on the layout page.
  if (!empty($_GET['destination']) && false !== strpos($_GET['destination'], 'layout/')) {
    unset($_GET['destination']);
  }

  $form_state['redirect'] = 'admin/layout';
}

/**
 * Layouts index admin page.
 */
function ucms_layout_admin_index() {

  return ['#markup' => "@todo"];
}
