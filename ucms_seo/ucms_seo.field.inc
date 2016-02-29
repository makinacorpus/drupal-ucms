<?php
/**
 * @file
 * Field API integration.
 */

/**
 * Implements hook_field_info().
 */
function ucms_seo_field_info() {
  return [
    'ucms_seo' => [
      'label'             => t("SEO information"),
      'description'       => t("Stores SEO information about the current node"),
      'settings'          => [],
      'instance_settings' => [],
      'default_widget'    => 'ucms_seo',
      'default_formatter' => 'hidden',
    ],
  ];
}

/**
 * Implements hook_field_widget_info().
 */
function ucms_seo_field_widget_info() {
  return [
    'ucms_seo' => [
      'label'       => t("SEO information"),
      'field types' => ['ucms_seo'],
      'settings'    => [],
    ],
  ];
}

/**
 * Implements hook_field_widget_settings_form().
 */
function ucms_seo_field_widget_settings_form($field, $instance) {
  $element = [];

  return $element;
}

/**
 * Implements hook_field_widget_form().
 */
function ucms_seo_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
/*
  if ('udate_legacy_date' === $instance['widget']['type']) {
    return udate_legacy_field_widget_form($form, $form_state, $field, $instance, $langcode, $items, $delta, $element);
  }

  switch ($instance['widget']['type']) {

    case 'udate':
      $keys = array('date' => null);
      break;

    case 'udate_range':
      $keys = array('date_start' => t("Start date"), 'date_end' => t("End date"));
      break;
  }

  if (!empty($keys)) {

    $elementType  = $instance['settings']['collectTime'] ? 'udatetime' : 'udate';
    $granularity  = $instance['settings']['granularity'];
    $dateFormat   = $instance['widget']['settings']['dateFormat'];
    $timeTitle    = $instance['widget']['settings']['timeTitle'];

    foreach ($keys as $key => $title) {

      $element[$key] = array(
        '#title'        => $title,
        '#type'         => $elementType,
        '#granularity'  => $granularity,
        '#dateFormat'   => $dateFormat,
        '#timeTitle'    => $timeTitle,
      );

      if (!empty($items[$delta][$key])) {
        // Invalid dates will become null so that's not a problem for us.
        $element[$key]['#default_value'] = $items[$delta][$key];
      }
    }

    $element['granularity'] = array(
      '#type'  => 'value',
      '#value' => $granularity,
    );
  }

  return $element;
   */
}

/**
 * Implements hook_field_is_empty().
 */
function ucms_seo_field_is_empty($item, $field) {
  // Always save SEO information even when empty.
  return false;
}
