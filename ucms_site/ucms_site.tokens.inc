<?php
/**
 * Token integration.
 */

use MakinaCorpus\Ucms\Site\Site;

/**
 * Implements hook_token_info().
 */
function ucms_site_token_info() {
  $ret = [
    'types' => [
      'current-site' => [
        'name' => t("Current site"),
        'description' => t("Tokens related to current site"),
      ],
      'current-site-attr' => [
        'name' => t("Current site attributes"),
        'description' => t("Current site variables and attributes"),
      ],
    ],
    'tokens' => [
      'current-site' => [
        'title' => [
          'name' => t("Site title"),
          'description' => t("Current site title"),
        ],
        'title_admin' => [
          'name' => t("Site administrative title"),
          'description' => t("Current site administrative title"),
        ],
        'hostname' => [
          'name' => t("Hostname"),
          'description' => t("Current site hostname, without 'http(s)://'"),
        ],
        'url' => [
          'name' => t("URL"),
          'description' => t("Current site URL, with 'http(s)://'"),
        ],
      ],
    ],
  ];

  foreach (module_invoke_all('ucms_site_token_attributes') as $name => $title) {
    $ret['tokens']['current-site-attr'][$name] = [
      'name' => $title,
      'description' => t("Site variable: @title", ['@title' => $title]),
    ];
  }

  return $ret;
}

/**
 * Implements hook_tokens().
 */
function ucms_site_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $ret = [];

  /** @var \MakinaCorpus\Ucms\Site\Site $site */
  if (!($site = $data['site'] ?? null) instanceof Site) {
    $manager = ucms_site_manager();
    if (!$manager->hasContext()) {
      return;
    }
    $site = $manager->getContext();
  }

  switch ($type) {

    case 'current-site':
      foreach ($tokens as $name => $original) {
        switch ($name) {

          case 'title':
            $ret[$original] = $site->getTitle();
            break;

          case 'title_admin':
            $ret[$original] = $site->getAdminTitle();
            break;

          case 'hostname':
            $ret[$original] = $site->getHostname();
            break;

          case 'url':
            /** @var \MakinaCorpus\Ucms\Site\SiteUrlGenerator $urlGenerator */
            $urlGenerator = \Drupal::service('ucms_site.url_generator');
            $ret[$original] = $urlGenerator->generateUrl($site, null, ['absolute' => true], true, true);
            break;
        }
      }
      break;

    case 'current-site-attr':
      foreach ($tokens as $name => $original) {
        if ($site->hasAttribute($name)) {
          $ret[$original] = (string)$site->getAttribute($name);
        }
      }
      break;
  }

  return $ret;
}
