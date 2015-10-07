<?php
/**
 * @file
 * AJAX callbacks.
 */

/**
 * Add favorite to cart.
 */
function ucms_contrib_ajax_favorite_add($node, $mode = 'nojs') {
  global $user;
  if (ucms_contrib_favorite_add($node->nid, $user->uid)) {
    $node_view = node_view($node, UCMS_VIEW_MODE_FAVORITE);
    $ret = [
      'success' => TRUE,
      'node' => drupal_render($node_view),
    ];
  }
  else {
    $ret = [
      'error' => t("%title is already a favorite", ['%title' => $node->title]),
    ];
    http_response_code(400);
  }
  drupal_json_output($ret);
}

/**
 * Remove favorite from cart.
 */
function ucms_contrib_ajax_favorite_remove($node, $mode = 'nojs') {
  global $user;
  ucms_contrib_favorite_remove($node->nid, $user->uid);
  $ret = [
    'success' => TRUE,
  ];
  drupal_json_output($ret);
}
