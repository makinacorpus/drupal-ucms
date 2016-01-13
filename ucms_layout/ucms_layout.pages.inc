<?php
/**
 * @file
 * Layout management user pages.
 */

use MakinaCorpus\Ucms\Layout\Layout;

/**
 * Layout delete form.
 */
function ucms_layout_admin_layout_delete_form($form, &$form_state, Layout $layout) {


}

/**
 * Used by the delete confirm form.
 */
function ucms_layout_admin_layout_delete_form_submit($form, &$form_state) {

}

/**
 * Layouts index admin page.
 */
function ucms_layout_admin_index() {

  $header = [
    [
      'data'  => t("Admin title"),
      'field' => 'l.title_admin',
      'sort'  => 'asc',
    ],
    [
      'data'  => t("Page title"),
      'field' => 'l.title',
    ],
    [
      'data'  => t("Owner"),
      'field' => 'l.title',
    ],
  ];
  if (module_exists('path')) {
    $header[] = [
      'data'  => t("Path")
    ];
  }
  $header[] = [
    'data'  => '',
  ];

  $rows = [];

  $q = db_select('ucms_layout', 'l');
  $q->join('users', 'u', 'u.uid = l.uid');
  $idList = $q
    ->fields('l', ['id'])
    ->extend('PagerDefault')
    ->limit(48)
    ->extend('TableSort')
    ->orderByHeader($header)
    ->execute()
    ->fetchCol()
  ;

  $accounts   = [];
  $anonymous  = drupal_anonymous_user();
  $layouts    = ucms_layout_context_get()->getStorage()->loadAll($idList);

  foreach ($layouts as $layout) {
    $accounts[$layout->getAccountId()] = $layout->getAccountId();
  }
  $accounts = user_load_multiple($accounts);

  foreach ($layouts as $layout) {

    $uid = $layout->getAccountId();
    if (isset($accounts[$uid])) {
      $name = format_username($accounts[$uid]);
    } else {
      $name = format_username($anonymous);
    }

    $row = [
      check_plain($layout->getAdminTitle()),
      check_plain($layout->getTitle()),
      $name,
    ];

    if (module_exists('path')) {
      $row[] = drupal_get_path_alias('layout/' . $layout->getId());
    }

    $links  = [];
    $base   = 'layout/' . $layout->getId();
    $query  = drupal_get_destination();
    if (ucms_layout_access('view', $layout)) {
      $links['view'] = [
        'href'  => $base . '/',
        'title' => t("View"),
      ];
    }
    if (ucms_layout_access('update', $layout)) {
      $links['edit'] = [
        'href'  => $base . '/edit',
        'title' => t("Update"),
        'query' => $query,
      ];
    }
    if (ucms_layout_access('delete', $layout)) {
      $links['delete'] = [
        'href'  => $base . '/delete',
        'title' => t("Delete"),
        'query' => $query,
      ];
    }

    $row[] = theme('links__ucms_layout_list_actions', ['links' => $links]);

    $rows[] = $row;
  }

  return [
    'table' => [
      '#theme'  => 'table',
      '#header' => $header,
      '#rows'   => $rows,
      '#empty'  => t("You have access to no existing pages."),
    ],
    'pager' => [
      '#theme' => 'pager',
    ],
  ];
}
