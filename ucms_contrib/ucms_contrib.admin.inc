<?php
/**
 * @file
 * Site wide configuration.
 */

/**
 * Main configuration form.
 */
function ucms_contrib_admin_structure_form($form, &$form_state) {

  $form['#tree'] = true;

  foreach (ucms_contrib_tab_list() as $tab => $name) {
    $form['tab'][$tab] = [
      '#title'  => t("%tab tab", ['%tab' => $name]),
      '#type'   => 'fieldset',
    ];
    $form['tab'][$tab]['types'] = [
      '#title'          => t("Content types"),
      '#type'           => 'checkboxes',
      '#options'        => node_type_get_names(),
      '#default_value'  => variable_get('ucms_contrib_tab_' . $tab .  '_type', []),
    ];
  }

  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = [
    '#type'   => 'submit',
    '#value'  => t('Save configuration')
  ];

  return $form;
}

/**
 * Main configuration form submit.
 */
function ucms_contrib_admin_structure_form_submit($form, &$form_state) {

  foreach ($form_state['values']['tab'] as $tab => $data) {

    // First process content types.
    $enabled = [];
    foreach ($data['types'] as $type => $status) {
      if ($status && $status === $type) {
        $enabled[] = $type;
      }
    }
    variable_set('ucms_contrib_tab_' . $tab . '_type', $enabled);
  }

  drupal_set_message(t('The configuration options have been saved.'));
}
