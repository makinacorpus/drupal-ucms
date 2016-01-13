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

  /* @var $cart \MakinaCorpus\Ucms\Contrib\CartStorage */
  $cart = \Drupal::service('ucms_contrib.cart');

  if ($cart->addFor($user->uid, $node->nid)) {
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

  /* @var $cart \MakinaCorpus\Ucms\Contrib\CartStorage */
  $cart = \Drupal::service('ucms_contrib.cart');

  $cart->removeFor($user->uid, $node->nid);

  $ret = [
    'success' => TRUE,
  ];

  drupal_json_output($ret);
}
