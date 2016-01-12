<?php
/**
 * @file
 * Dashboard related pages.
 */

use MakinaCorpus\Ucms\Site\Site;

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

  if (empty($form_state['storage']['site'])) {
    $site = $form_state['storage']['site'] = new Site();
    $site->uid = $GLOBALS['user']->uid;
  } else {
    $site = $form_state['storage']['site'];
  }

  if (empty($form_state['storage']['step'])) {
    $step = 'a';
  } else {
    $step = $form_state['storage']['step'];
  }

  switch ($step) {

    case 'a':
      return ucms_site_dashboard_site_request_form_step_a($form, $form_state, $site);
      break;

    case 'b':
      return ucms_site_dashboard_site_request_form_step_b($form, $form_state, $site);
      break;
  }
}

/**
 * Request new site form (step a).
 */
function ucms_site_dashboard_site_request_form_step_a($form, &$form_state, Site $site) {

  $form['title'] = [
    '#title'          => t("Name"),
    '#type'           => 'textfield',
    '#default_value'  => $site->title,
    '#attributes'     => ['placeholder' => t("Martray's optical")],
    '#description'    => t("This will appear on the site as the site title"),
    '#required'       => true,
  ];

  $form['title_admin'] = [
    '#title'          => t("Description"),
    '#type'           => 'textarea',
    '#default_value'  => $site->title_admin,
    '#description'    => t("This will be as the site's administrative description in platform backoffice"),
    '#required'       => true,
  ];

  $form['http_host'] = [
    '#title'          => t("Host name"),
    '#type'           => 'textfield',
    '#field_prefix'   => "http://",
    '#default_value'  => $site->http_host,
    '#attributes'     => ['placeholder' => "martray-optique.fr"],
    '#description'    => t("Type here the site URL"),
    '#required'       => true,
  ];

  // @todo Missing site type

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

  /** @var $site Site */
  $site = $form_state['storage']['site'];
  $site->title = $form_state['values']['title'];
  $site->title_admin = $form_state['values']['title_admin'];
  $site->http_host = $form_state['values']['http_host'];

  $form_state['storage']['step'] = 'b';
  $form_state['rebuild'] = true;
}

/**
 * Request new site form (step b).
 */
function ucms_site_dashboard_site_request_form_step_b($form, &$form_state, Site $site) {

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

  /** @var $site Site */
  $site = $form_state['storage']['site'];
  $site->state = 0;

  ucms_site_finder()->save($site);
  drupal_set_message(t("Your site creation request has been submitted"));
}
