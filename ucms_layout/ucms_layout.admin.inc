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
