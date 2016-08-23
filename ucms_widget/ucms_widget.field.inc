<?php
/**
 * @file
 * Field handling related functions.
 */

use MakinaCorpus\Ucms\Widget\WidgetInterface;

/**
 * Implements hook_field_info().
 */
function ucms_widget_field_info() {
  return [
    'ucms_widget' => [
      'label'             => t("Widget"),
      'description'       => t("Widgets are basically value-less field implementation with their specific rendering pipeline"),
      'settings'          => ['type' => null],
      'instance_settings' => [],
      'default_widget'    => 'ucms_widget',
      'default_formatter' => 'ucms_widget',
    ],
  ];
}

/**
 * Implements hook_field_settings_form().
 */
function ucms_widget_field_settings_form($field, $instance, $has_data) {
  return [
    'type' => [
      '#type'           => 'select',
      '#title'          => t("Type"),
      '#options'        => ucms_widget_registry()->getAllNames(),
      '#empty_option'   => t("Select widget type"),
      '#default_value'  => $field['settings']['type'],
      '#required'       => true,
    ],
  ];
}

/**
 * Implements hook_field_widget_info().
 */
function ucms_widget_field_formatter_info() {
  return [
    'ucms_widget' => [
      'label'       => t("Widget display"),
      'field types' => ['ucms_widget'],
      'settings'    => ['options' => []],
    ],
  ];
}

/**
 * Get formatter options from instance
 *
 * @param WidgetInterface $widget
 * @param mixed[] $instance
 *
 * @return mixed[]
 */
function _ucms_widget_formatter_get_options(WidgetInterface $widget, $instance, $display) {

  $settings = $display['settings']['options'];
  $defaults = $widget->getDefaultFormatterOptions();

  return drupal_array_merge_deep($defaults, $settings);
}

/**
 * Implements hook_field_formatter_settings_form().
 */
function ucms_widget_field_formatter_settings_form($field, $instance, $view_mode, $form, &$form_state) {
  $element = [];

  $widget   = ucms_widget_registry()->get($field['settings']['type']);
  $display  = $instance['display'][$view_mode];
  $settings = _ucms_widget_formatter_get_options($widget, $instance, $display);

  // Fetch form with values
  $localForm = $widget->getFormatterOptionsForm($settings);
  if (is_string($localForm)) {
    $element['options'] = \Drupal::formBuilder()->getForm($localForm, $settings);
  }
  if (is_array($localForm)) {
    $element['options'] = $localForm;
  }

  return $element;
}

/**
 * Implements hook_field_formatter_settings_summary().
 */
function ucms_widget_field_formatter_settings_summary($field, $instance, $view_mode) {
  return "&hellip";
}

/**
 * Implements hook_field_formatter_view().
 */
function ucms_widget_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $element = [];

  $manager = ucms_site_manager();
  if (!$manager->hasContext()) {
    return $element;
  }

  if ($items) {
    $site = $manager->getContext();

    $widget = ucms_widget_registry()->get($field['settings']['type']);
    $defaults = $widget->getDefaultOptions();
    $formatterOptions = _ucms_widget_formatter_get_options($widget, $instance, $display);

    foreach ($items as $delta => $item) {
      $settings = drupal_array_merge_deep($defaults, $item['options']);

      $output = $widget->render($entity, $site, $settings, $formatterOptions);
      if (!is_array($output)) {
        $output = ['#markup' => $output];
      }

      $element[$delta] = $output;
    }
  }

  return $element;
}

/**
 * Implements hook_field_widget_info().
 */
function ucms_widget_field_widget_info() {
  return [
    'ucms_widget' => [
      'label'       => t("Widget options"),
      'field types' => ['ucms_widget'],
      'settings'    => [],
    ],
  ];
}

/**
 * Implements hook_field_widget_settings_form().
 */
function ucms_widget_field_widget_settings_form($field, $instance) {
  return [];
}

/**
 * Implements hook_field_widget_form().
 */
function ucms_widget_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  $widget = ucms_widget_registry()->get($field['settings']['type']);

  if (!$items) {
    // This happens in Field UI for default value, let's make it happy
    $items[$delta] = ['options' => []];
  }

  // Compute defaults
  $defaults = $widget->getDefaultOptions();
  $settings = drupal_array_merge_deep($defaults, $items[$delta]['options']);

  // Fetch form with values
  $localForm = $widget->getOptionsForm($settings);
  if (is_string($localForm)) {
    $element['options'] = \Drupal::formBuilder()->getForm($localForm, $settings);
  }
  if (is_array($localForm)) {
    $element['options'] = $localForm;
  }

  return $element;
}

/**
 * Implements hook_field_attach_presave().
 *
 * We use this hook instead of hook_field_presave() because it gets executed
 * just before the real save, and allows us to avoid potential conflicts with
 * other modules.
 *
 * @see ucms_widget_field_load()
 */
function ucms_widget_field_attach_presave($entity_type, $entity) {
  list(,, $bundle) = entity_extract_ids($entity_type, $entity);
  foreach (field_info_instances($entity_type, $bundle) as $instance) {
    $field_name = $instance['field_name'];

    $field = field_info_field($field_name);
    if (!$field || 'ucms_widget' !== $field['type']) {
      continue;
    }

    // Field API magic: foreach-ception.
    if (!empty($entity->{$field_name})) {
      foreach ($entity->{$field_name} as $language => $items) {
        foreach ($items as $delta => $item) {
          if ($item['options']) {
            $entity->{$field_name}[$language][$delta]['options'] = serialize($item['options']);
          } else {
            $entity->{$field_name}[$language][$delta]['options'] = null;
          }
        }
      }
    }
  }
}

/**
 * Implements hook_field_load().
 *
 * @see ucms_widget_field_attach_presave()
 */
function ucms_widget_field_load($entity_type, $entities, $field, $instances, $langcode, &$items, $age) {
  if ($items) {
    foreach ($items as $id => $entityItems) {
      foreach ($entityItems as $delta => $item) {
        if ($item['options']) {
          $items[$id][$delta]['options'] = unserialize($item['options']);
        } else {
          $item['options'] = [];
        }
      }
    }
  }
}

/**
 * Implements hook_field_is_empty().
 */
function ucms_widget_field_is_empty($item, $field) {
  return false;
}
