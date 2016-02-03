<?php
/**
 * @file
 * Dashboard related pages.
 */

use MakinaCorpus\Ucms\Dashboard\Page\Page;
use MakinaCorpus\Ucms\Site\Page\SiteAdminDisplay;
use MakinaCorpus\Ucms\Site\Site;

/**
 * Main site list page.
 */
function ucms_site_dashboard_site_list() {
  return ucms_dashboard_page_get(\Drupal::service('ucms_site.admin.datasource'), new SiteAdminDisplay(), ['dashboard', 'site'])->render();
}
