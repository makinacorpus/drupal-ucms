<?php


/**
 * Implements hook_field_formatter_info().
 */
function ucms_contrib_field_formatter_info() {
  return [
    'ucms_unoderef_clone_aware' => [
      'label'       => t("Clone aware"),
      'field types' => ['unoderef'],
      'settings'    => [
        'view_mode' => 'teaser',
      ],
    ],
    'ucms_unoderef_clone_aware_first' => [
      'label'       => t("Clone aware (first item only)"),
      'field types' => ['unoderef'],
      'settings'    => [
        'view_mode' => 'teaser',
      ],
    ],
    'ucms_ulink_clone_aware' => [
      'label'       => t("Clone aware"),
      'field types' => ['ulink'],
      'settings'    => [
        'trim_length' => 80,
        'nofollow' => false,
      ],
    ],
  ];
}


/**
 * Implements hook_field_formatter_settings_form().
 */
function ucms_contrib_field_formatter_settings_form($field, $instance, $view_mode, $form, &$form_state) {
  $display = $instance['display'][$view_mode];

  switch ($display['type']) {
    case 'ucms_unoderef_clone_aware':
    case 'ucms_unoderef_clone_aware_first':
      $element = unoderef_field_formatter_settings_form($field, $instance, $view_mode, $form, $form_state);
      break;
    case 'ucms_ulink_clone_aware':
      $element = ulink_field_formatter_settings_form($field, $instance, $view_mode, $form, $form_state);
      break;
  }

  return $element;
}


/**
 * Implements hook_field_formatter_settings_summary().
 */
function ucms_contrib_field_formatter_settings_summary($field, $instance, $view_mode) {
  $display = $instance['display'][$view_mode];

  switch ($display['type']) {
    case 'ucms_unoderef_clone_aware':
    case 'ucms_unoderef_clone_aware_first':
      return unoderef_field_formatter_settings_summary($field, $instance, $view_mode);
    case 'ucms_ulink_clone_aware':
      return ulink_field_formatter_settings_summary($field, $instance, $view_mode);
  }

  return "&hellip"; // Summary must not be empty.
}


/**
 * Implements hook_field_formatter_view().
 */
function ucms_contrib_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $element = [];
  $settings = $display['settings'];
  if (isset($settings['view_mode'])) {
    $view_mode = $settings['view_mode'];
  } else {
    $view_mode = 'default';
  }

  switch ($display['type']) {

    case 'ucms_unoderef_clone_aware_first':
      // Just reduces the items array and lets the next case do the job.
      $items = array_slice($items, 0, 1);

    case 'ucms_unoderef_clone_aware':
      $context = ucms_site_manager()->getContext();

      if ($context) {
        $nids = [];
        $mapping = \Drupal::service('ucms_site.node_manager')->getCloningMapping($context);

        foreach ($items as $item) {
          $nid = $item['nid'];
          if (isset($mapping[$nid])) {
            $nid = $mapping[$nid];
          }
          $nids[] = $nid;
        }
      } else {
        $nids = array_column($items, 'nid');
      }

      if (count($nids)) {
        $nodes = node_load_multiple($nids);
        $views = node_view_multiple($nodes, $view_mode);
        foreach ($nids as $delta => $nid) {
          if (isset($views['nodes'][$nid])) {
            $element[$delta] = $views['nodes'][$nid];
          }
        }
      }
      break;

    case 'ucms_ulink_clone_aware':
      $context = ucms_site_manager()->getContext();

      if ($context) {
        $mapping = \Drupal::service('ucms_site.node_manager')->getCloningMapping($context);
        $generator = \Drupal::service('ulink.entity_link_generator');

        foreach ($items as $delta => &$item) {
          if (
            ($parts = $generator->decomposeURI($item['value'], $uri_type)) &&
            isset($mapping[(int) $parts['id']])
          ) {
            $item['value'] = $generator->formatURI($parts['type'], $mapping[(int) $parts['id']], $uri_type);
          }
        }
      }

      $element = ulink_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display);
      break;
  }

  return $element;
}
