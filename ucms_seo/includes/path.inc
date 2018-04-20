<?php
/**
 * @file
 * path.inc replacement that completely disable Drupal alias handling.
 *
 * For performance reasons, and performance reasons only, this file is
 * overrded by the ucms_site_boot() function. It is so we can avoid
 * ucms_seo_boot() implementation, and we don't have to deal with module
 * loading order.
 */

/**
 * {@inheritdoc}
 */
function drupal_path_initialize() {
  if (empty($_GET['q'])) {
    $_GET['q'] = variable_get('site_frontpage', 'node');
  }
}

/**
 * {@inheritdoc}
 */
function drupal_lookup_path($action, $path = '', $path_language = NULL) {
  switch ($action) {

    case 'wipe':
      return false;

    case 'alias':
    case 'source':
      return $path;
  }
}

/**
 * {@inheritdoc}
 */
function drupal_cache_system_paths() {}

/**
 * {@inheritdoc}
 */
function drupal_get_path_alias($path = NULL, $path_language = NULL) {
  if ($path == NULL) {
    $path = $_GET['q'];
  }
  return $path;
}

/**
 * {@inheritdoc}
 */
function drupal_path_alias_whitelist_rebuild($source = NULL) {}

/**
 * {@inheritdoc}
 */
function path_load($conditions) {
  return FALSE;
}

/**
 * {@inheritdoc}
 */
function path_save(&$path, $clearCaches = true) {}

/**
 * {@inheritdoc}
 */
function path_delete($criteria) {}

/**
 * {@inheritdoc}
 */
function drupal_clear_path_cache($source = NULL) {}

// Code below this line is copy/pasted core code.

/**
 * {@inheritdoc}
 *
 * COPY/PASTE OF CORE CODE.
 */
function drupal_get_normal_path($path, $path_language = NULL) {
  $original_path = $path;

  if ($path == NULL) {
    $path = $_GET['q'];
  }

  // Allow other modules to alter the inbound URL. We cannot use drupal_alter()
  // here because we need to run hook_url_inbound_alter() in the reverse order
  // of hook_url_outbound_alter().
  foreach (array_reverse(module_implements('url_inbound_alter')) as $module) {
    $function = $module . '_url_inbound_alter';
    $function($path, $original_path, $path_language);
  }

  return $path;
}

/**
 * {@inheritdoc}
 *
 * COPY/PASTE OF CORE CODE.
 */
function drupal_is_front_page() {
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['is_front_page'] = &drupal_static(__FUNCTION__);
  }
  $is_front_page = &$drupal_static_fast['is_front_page'];

  if (!isset($is_front_page)) {
    // As drupal_path_initialize updates $_GET['q'] with the 'site_frontpage' path,
    // we can check it against the 'site_frontpage' variable.
    $is_front_page = ($_GET['q'] == variable_get('site_frontpage', 'node'));
  }

  return $is_front_page;
}

/**
 * {@inheritdoc}
 *
 * COPY/PASTE OF CORE CODE.
 */
function drupal_match_path($path, $patterns) {
  $regexps = &drupal_static(__FUNCTION__);

  if (!isset($regexps[$patterns])) {
    // Convert path settings to a regular expression.
    // Therefore replace newlines with a logical or, /* with asterisks and the <front> with the frontpage.
    $to_replace = array(
      '/(\r\n?|\n)/', // newlines
      '/\\\\\*/',     // asterisks
      '/(^|\|)\\\\<front\\\\>($|\|)/' // <front>
    );
    $replacements = array(
      '|',
      '.*',
      '\1' . preg_quote(variable_get('site_frontpage', 'node'), '/') . '\2'
    );
    $patterns_quoted = preg_quote($patterns, '/');
    $regexps[$patterns] = '/^(' . preg_replace($to_replace, $replacements, $patterns_quoted) . ')$/';
  }
  return (bool)preg_match($regexps[$patterns], $path);
}

/**
 * {@inheritdoc}
 *
 * COPY/PASTE OF CORE CODE.
 */
function current_path() {
  return $_GET['q'];
}

/**
 * {@inheritdoc}
 *
 * COPY/PASTE OF CORE CODE.
 */
function path_is_admin($path) {
  $path_map = &drupal_static(__FUNCTION__);
  if (!isset($path_map['admin'][$path])) {
    $patterns = path_get_admin_paths();
    $path_map['admin'][$path] = drupal_match_path($path, $patterns['admin']);
    $path_map['non_admin'][$path] = drupal_match_path($path, $patterns['non_admin']);
  }
  return $path_map['admin'][$path] && !$path_map['non_admin'][$path];
}

/**
 * {@inheritdoc}
 *
 * COPY/PASTE OF CORE CODE.
 */
function path_get_admin_paths() {
  $patterns = &drupal_static(__FUNCTION__);
  if (!isset($patterns)) {
    $paths = module_invoke_all('admin_paths');
    drupal_alter('admin_paths', $paths);
    // Combine all admin paths into one array, and likewise for non-admin paths,
    // for easier handling.
    $patterns = array();
    $patterns['admin'] = array();
    $patterns['non_admin'] = array();
    foreach ($paths as $path => $enabled) {
      if ($enabled) {
        $patterns['admin'][] = $path;
      }
      else {
        $patterns['non_admin'][] = $path;
      }
    }
    $patterns['admin'] = implode("\n", $patterns['admin']);
    $patterns['non_admin'] = implode("\n", $patterns['non_admin']);
  }
  return $patterns;
}

/**
 * {@inheritdoc}
 *
 * COPY/PASTE OF CORE CODE.
 */
function drupal_valid_path($path, $dynamic_allowed = FALSE) {
  global $menu_admin;
  // We indicate that a menu administrator is running the menu access check.
  $menu_admin = TRUE;
  if ($path == '<front>' || url_is_external($path)) {
    $item = array('access' => TRUE);
  }
  elseif ($dynamic_allowed && preg_match('/\/\%/', $path)) {
    // Path is dynamic (ie 'user/%'), so check directly against menu_router table.
    if ($item = db_query("SELECT * FROM {menu_router} where path = :path", array(':path' => $path))->fetchAssoc()) {
      $item['link_path']  = $item['path'];
      $item['link_title'] = $item['title'];
      $item['external']   = FALSE;
      $item['options'] = '';
      _menu_link_translate($item);
    }
  }
  else {
    $item = menu_get_item($path);
  }
  $menu_admin = FALSE;
  return $item && $item['access'];
}
