<?php
/**
 * @file
 * Dashboard related pages.
 */

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Dashboard\Page\Page;

/**
 * Main site list page.
 */
function ucms_site_dashboard_site_list() {
  return (new Page(\Drupal::service('ucms_site.admin.datasource'), ['dashboard', 'site']))->render();
}
