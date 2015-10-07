<?php
/**
 * @file
 * AJAX callbacks.
 */

/**
 * Add item to a a region callback.
 *
 * @param \Ucms\Layout\Layout $layout
 * @return int
 */
function ucms_layout_ajax_region_item_add($layout) {

  if ('POST' !== $_SERVER['REQUEST_METHOD']) {
    return MENU_NOT_FOUND;
  }

  $query = $_POST;

  // CSRF protection.
/*  if (empty($query['token'])) {
    return MENU_ACCESS_DENIED; // No CSRF token.
  }
  if (!drupal_valid_token($query['token'])) {
    return MENU_ACCESS_DENIED; // Invalid CSRF token.
  }*/

  // Input validation.
  if (!$layout instanceof \Ucms\Layout\Layout) {
    return MENU_NOT_FOUND;
  }
  if (empty($query['region'])) {
    return MENU_NOT_FOUND;
  }
  if (empty($query['nid'])) {
    return MENU_NOT_FOUND;
  }
  if (!$node = node_load($query['nid'])) {
    return MENU_NOT_FOUND;
  }
  if (!node_access('view', $node)) {
    return MENU_ACCESS_DENIED;
  }
  if (empty($query['view_mode'])) {
    $query['view_mode'] = 'teaser';
  }

  $item = new \Ucms\Layout\Item($query['nid'], $query['view_mode']);

  switch ($query['mode']) {

    case 'prepend':
      $layout->getRegion($query['region'])->prepend($item);
      break;

    case 'append':
      $layout->getRegion($query['region'])->append($item);
      break;

    default:
      if (empty($query['position'])) {
        // Fallback to append.
        $layout->getRegion($query['region'])->append($item);
      } else {
        $layout->getRegion($query['region'])->addAt($item, $query['position']);
      }
      break;
  }

  ucms_layout_save($layout);

  drupal_json_output(['success' => true]);
}

/**
 * Add item to a a region callback.
 *
 * @param \Ucms\Layout\Layout $layout
 */
function ucms_layout_ajax_region_item_remove($layout) {

  if ('POST' !== $_SERVER['REQUEST_METHOD']) {
    return MENU_NOT_FOUND;
  }

  $query = $_POST;

  // CSRF protection.
  if (empty($query['token'])) {
    return MENU_ACCESS_DENIED; // No CSRF token.
  }
  if (!drupal_valid_token($query['token'])) {
    return MENU_ACCESS_DENIED; // Invalid CSRF token.
  }

  // Input validation.
  if (!$layout instanceof \Ucms\Layout\Layout) {
    return MENU_NOT_FOUND;
  }
  if (empty($query['region'])) {
    return MENU_NOT_FOUND;
  }
  if (empty($query['position'])) {
    $query['position'] = 0;
  }

  $layout->getRegion($query['region'])->removeAt($query['position']);

  ucms_layout_save($layout);

  drupal_json_output(['success' => true]);
}
