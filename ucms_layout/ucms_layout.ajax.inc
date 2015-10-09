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

  $layout->getRegion($query['region'])->addAt($item, $query['position']);

  ucms_layout_context_get()->getStorage()->save($layout);

  $node_view = node_view($node, $query['view_mode']);
  drupal_json_output([
    'success' => TRUE,
    'node' => drupal_render($node_view),
  ]);
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

  // FIXME VERY UGLY
  $lock_name = 'layout_update:' . $layout->getId();
  $oups = false;
  while (!lock_may_be_available($lock_name)) {
    sleep(0.05);
    $oups = true;
  }
  if (!lock_acquire($lock_name)) {
    drupal_json_output(['success' => false]);
    return;
  }
  if ($oups) {
    $layout = ucms_layout_context_get()->getStorage()->load($layout->getId());
  }

  $layout->getRegion($query['region'])->removeAt($query['position']);

  ucms_layout_context_get()->getStorage()->save($layout);

  drupal_json_output(['success' => true]);
}


/**
 * Add item to a a region callback.
 *
 * @param \Ucms\Layout\Layout $layout
 * @return int
 */
function ucms_layout_ajax_region_item_move($layout) {

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

  // FIXME VERY UGLY
  $lock_name = 'layout_update:' . $layout->getId();
  $oups = false;
  while (!lock_may_be_available($lock_name)) {
    sleep(0.05);
    $oups = true;
  }
  if (!lock_acquire($lock_name)) {
    drupal_json_output(['success' => false]);
    return;
  }
  if ($oups) {
    $layout = ucms_layout_context_get()->getStorage()->load($layout->getId());
  }

  $item = new \Ucms\Layout\Item($query['nid'], $query['view_mode']);

  $layout->getRegion($query['region'])->removeAt($query['prevPosition']);
  $layout->getRegion($query['region'])->addAt($item, $query['position']);

  ucms_layout_context_get()->getStorage()->save($layout);

  $node_view = node_view($node, $query['view_mode']);
  drupal_json_output([
    'success' => TRUE,
    'node' => drupal_render($node_view),
  ]);
}
