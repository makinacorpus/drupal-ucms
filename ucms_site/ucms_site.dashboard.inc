<?php
/**
 * @file
 * Dashboard related pages.
 */

/**
 * Main site list page.
 */
function ucms_site_dashboard_site_list() {
  return [];
}

/**
 * Request new site form.
 */
function ucms_site_dashboard_site_request_form($form, &$form_state) {

  if (empty($form_state['storage']['step'])) {
    $step = 'a';
  } else {
    $step = $form_state['storage']['step'];
  }

  switch ($step) {

    case 'a':
      return ucms_site_dashboard_site_request_form_step_a($form, $form_state);
      break;

    case 'b':
      return ucms_site_dashboard_site_request_form_step_b($form, $form_state);
      break;
  }
}

/**
 * Request new site form (step a).
 */
function ucms_site_dashboard_site_request_form_step_a($form, &$form_state) {

  // @todo Form stuff

  $form['actions']['#type'] = 'actions';
  $form['actions']['continue'] = [
    '#type'   => 'submit',
    '#value'  => t("Continue"),
    '#submit' => ['ucms_site_dashboard_site_request_form_step_a_submit_continue'],
  ];
  $form['actions']['cancel'] = [
    '#markup' => l(
      t("Cancel"),
      isset($_GET['destination']) ? $_GET['destination'] : 'admin/dashboard/site',
      ['attributes' => ['class' => ['btn', 'btn-danger']]]
    ),
  ];

  return $form;
}

/**
 * Request new site form (step a) continue.
 */
function ucms_site_dashboard_site_request_form_step_a_submit_continue($form, &$form_state) {
  // @todo Set values in form state
  $form_state['storage']['step'] = 'b';
  $form_state['rebuild'] = true;
}

/**
 * Request new site form (step b).
 */
function ucms_site_dashboard_site_request_form_step_b($form, &$form_state) {

  // @todo Form stuff

  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = [
    '#type'   => 'submit',
    '#value'  => t("Request"),
    '#submit' => ['ucms_site_dashboard_site_request_form_step_b_submit'],
  ];
  $form['actions']['back'] = [
    '#type'   => 'submit',
    '#value'  => t("Go back"),
    '#submit' => ['ucms_site_dashboard_site_request_form_step_b_submit_back'],
  ];

  return $form;
}

/**
 * Request new site form (step b) go back.
 */
function ucms_site_dashboard_site_request_form_step_b_submit_back($form, &$form_state) {
  // @todo Set values in form state
  $form_state['storage']['step'] = 'a';
  $form_state['rebuild'] = true;
}

/**
 * Request new site form (step b) submit.
 */
function ucms_site_dashboard_site_request_form_step_b_submit($form, &$form_state) {
  // @todo Insert site into database.
}
