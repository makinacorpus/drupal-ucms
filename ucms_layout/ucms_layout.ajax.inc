<?php
/**
 * @file
 * AJAX callbacks.
 */

use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Layout\ContextManager;
use MakinaCorpus\Ucms\Layout\Item;
use MakinaCorpus\Ucms\Layout\Layout;

/**
 * Add item to a a region callback.
 *
 * @param Layout $layout
 * @return int
 */
function ucms_layout_ajax_region_item_add($layout) {

  if ('POST' !== $_SERVER['REQUEST_METHOD']) {
    return MENU_NOT_FOUND;
  }

  $query = $_POST;

  // CSRF protection.
  if (empty($query[ContextManager::PARAM_AJAX_TOKEN])) {
    return MENU_ACCESS_DENIED; // No CSRF token.
  }
  if (!drupal_valid_token($query[ContextManager::PARAM_AJAX_TOKEN])) {
    return MENU_ACCESS_DENIED; // Invalid CSRF token.
  }

  // Input validation.
  if (!$layout instanceof Layout) {
    return MENU_NOT_FOUND;
  }
  if (empty($query['region'])) {
    return MENU_NOT_FOUND;
  }
  if (empty($query['nid'])) {
    return MENU_NOT_FOUND;
  }
  /* @var $node NodeInterface */
  if (!$node = node_load($query['nid'])) {
    return MENU_NOT_FOUND;
  }
  if (!$node->access('view')) {
    return MENU_ACCESS_DENIED;
  }
  if (empty($query['view_mode'])) {
    $query['view_mode'] = 'teaser';
  }

  $manager = ucms_layout_context_manager();
  $site = ucms_site_manager()->getContext();
  $item = new Item($query['nid'], $query['view_mode']);

  $layout->getRegion($query['region'])->addAt($item, $query['position']);

  if ($manager->isPageContextRegion($query['region'], $site->theme)) {
    $manager->getPageContext()->getStorage()->save($layout);
  }
  elseif ($manager->isTransversalContextRegion($query['region'], $site->theme)) {
    $manager->getSiteContext()->getStorage()->save($layout);
  }
  else {
    return MENU_ACCESS_DENIED;
  }

  $node_view = node_view($node, $query['view_mode']);
  drupal_json_output([
    'success' => TRUE,
    'node' => drupal_render($node_view),
  ]);
}

/**
 * Add item to a a region callback.
 *
 * @param Layout $layout
 */
function ucms_layout_ajax_region_item_remove($layout) {

  if ('POST' !== $_SERVER['REQUEST_METHOD']) {
    return MENU_NOT_FOUND;
  }

  $query = $_POST;

  // CSRF protection.
  if (empty($query[ContextManager::PARAM_AJAX_TOKEN])) {
    return MENU_ACCESS_DENIED; // No CSRF token.
  }
  if (!drupal_valid_token($query[ContextManager::PARAM_AJAX_TOKEN])) {
    return MENU_ACCESS_DENIED; // Invalid CSRF token.
  }

  // Input validation.
  if (!$layout instanceof Layout) {
    return MENU_NOT_FOUND;
  }
  if (empty($query['region'])) {
    return MENU_NOT_FOUND;
  }
  if (empty($query['position'])) {
    $query['position'] = 0;
  }

  $manager = ucms_layout_context_manager();
  $site = ucms_site_manager()->getContext();
  $layout->getRegion($query['region'])->removeAt($query['position']);

  if ($manager->isPageContextRegion($query['region'], $site->theme)) {
    $manager->getPageContext()->getStorage()->save($layout);
  }
  elseif ($manager->isTransversalContextRegion($query['region'], $site->theme)) {
    $manager->getSiteContext()->getStorage()->save($layout);
  }
  else {
    return MENU_ACCESS_DENIED;
  }

  drupal_json_output(['success' => true]);
}


/**
 * Add item to a a region callback.
 *
 * @param Layout $layout
 * @return int
 */
function ucms_layout_ajax_region_item_move($layout) {

  if ('POST' !== $_SERVER['REQUEST_METHOD']) {
    return MENU_NOT_FOUND;
  }

  $query = $_POST;

  // CSRF protection.
  if (empty($query[ContextManager::PARAM_AJAX_TOKEN])) {
    return MENU_ACCESS_DENIED; // No CSRF token.
  }
  if (!drupal_valid_token($query[ContextManager::PARAM_AJAX_TOKEN])) {
    return MENU_ACCESS_DENIED; // Invalid CSRF token.
  }

  // Input validation.
  if (!$layout instanceof Layout) {
    return MENU_NOT_FOUND;
  }
  if (empty($query['region'])) {
    return MENU_NOT_FOUND;
  }
  if (empty($query['nid'])) {
    return MENU_NOT_FOUND;
  }
  /* @var $node NodeInterface */
  if (!$node = node_load($query['nid'])) {
    return MENU_NOT_FOUND;
  }
  if (!$node->access('view')) {
    return MENU_ACCESS_DENIED;
  }
  if (empty($query['view_mode'])) {
    $query['view_mode'] = 'teaser';
  }

  $manager = ucms_layout_context_manager();
  $site = ucms_site_manager()->getContext();
  $item = new Item($query['nid'], $query['view_mode']);

  if (!empty($query['prevRegion'])) {
    $layout->getRegion($query['prevRegion'])->removeAt($query['prevPosition']);
  } else {
    $layout->getRegion($query['region'])->removeAt($query['prevPosition']);
  }

  $layout->getRegion($query['region'])->addAt($item, $query['position']);

  if ($manager->isPageContextRegion($query['region'], $site->theme)) {
    $manager->getPageContext()->getStorage()->save($layout);
  }
  elseif ($manager->isTransversalContextRegion($query['region'], $site->theme)) {
    $manager->getSiteContext()->getStorage()->save($layout);
  }
  else {
    return MENU_ACCESS_DENIED;
  }

  $node_view = node_view($node, $query['view_mode']);
  drupal_json_output([
    'success' => TRUE,
    'node' => drupal_render($node_view),
  ]);
}
