<?php
/**
 * @file
 * Site-wide administration pages.
 */

/**
 * Main layout listing screen.
 */
function ucms_layout_admin_structure() {

  $header = [t("Theme"), t("Enabled regions"), ""];
  $rows   = [];

  foreach (system_list('theme') as $theme => $info) {

    if (!$info->status) {
      continue;
    }

    $all      = system_region_list($theme);
    $enabled  = ucms_layout_theme_region_list($theme);
    $links    = [];

    $links[] = l(t("Edit"), 'admin/structure/layout/' . $theme, ['query' => drupal_get_destination()]);

    $rows[] = [
      $info->name,
      implode(', ', array_intersect_key($all, $enabled)),
      theme('item_list__ucms_search_region_actions', ['items' => $links]),
    ];
  }

  return [
    '#theme'  => 'table',
    '#header' => $header,
    '#rows'   => $rows,
    '#empty'  => t("No theme found."),
  ];
}

/**
 * Edit theme related settings form.
 */
function ucms_layout_admin_structure_theme_form($form, &$form_state, $theme) {

  $form['#theme_key'] = $theme;

  $all      = system_region_list($theme);
  $enabled  = ucms_layout_theme_region_list($theme);

  $form['regions'] = [
    '#title'          => t("Enabled regions"),
    '#type'           => 'checkboxes',
    '#options'        => $all,
    '#default_value'  => $enabled,
    '#description'    => t("Uncheck all regions if you do not with layouts to be usable with this theme."),
  ];

  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = [
    '#type'   => 'submit',
    '#value'  => t('Save configuration')
  ];

  return $form;
}

/**
 * Edit theme related settings form submit.
 */
function ucms_layout_admin_structure_theme_form_submit($form, &$form_state) {

  // Save enabled regions.
  $enabled = [];
  foreach ($form_state['values']['regions'] as $region => $status) {
    if ($status && $status === $region) {
      $enabled[] = $region;
    }
  }
  variable_set('ucms_layout_regions_' . $form['#theme_key'], $enabled);

  drupal_set_message(t('The configuration options have been saved.'));
}
