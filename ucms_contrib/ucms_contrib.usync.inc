<?php

/**
 * Implements hook_usync_path_map().
 */
function ucms_contrib_usync_path_map() {
  return ['entity.?type.?bundle.behavior' => '\MakinaCorpus\Ucms\Contrib\USync\AST\Drupal\ContentTypeBehaviorNode'];
}

/**
 * Implements hook_usync_loader_list().
 */
function ucms_contrib_usync_loader_list() {
  return [Drupal::service('ucms_contrib.usync_behavior_loader')];
}
