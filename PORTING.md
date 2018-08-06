# Pending port to Drupal 8.x

## Minimal viable product

 - [x] DELETED - site: restore post init event
 - [x] FIXED - dashboard: bug in calista search (search parameter override itself)
 - [x] FIXED - site: finish site listing screen
 - [x] FIXED - site: get rid once for all of Drupal role to site role relationship
 - [x] FIXED - site: restore menu site status alteration
 - [x] FIXED - site: restore site access query alter
 - [x] HARDCODED - site: restore state transition matrix
 - [x] PORTED - site: restore node access query alter
 - [x] PORTED - ucms_dashboard
 - [x] PORTED - ucms_site
 - [x] PORTED - umenu
 - all: fixe destination parameter usage
 - all: handle caching gracefully
 - all: restore SQL constraints
 - dashboard: improve page controller trait
 - dashboard: improve seven fixes
 - dashboard: untangle display skin
 - PENDING - Modern front assets toolchain
 - sf-int: write a proxy to router access check system to symfony authorization checker
 - site: fix cross url route generator to use path matching
 - site: fix missing site frontpage (home node)
 - site: plug ucms_site on umenu using MenuEnvEvent to set site_id condition
 - ucms_contrib
 - ucms_tree

## Necessary

 - [x] DELETED - contrib: cart must die
 - [x] DELETED - contrib: remove custom ckeditor customisations
 - [x] FIXED - all: userCanX() methods must die, then merge NodeAccessService into SiteAccessService
 - all: rewrite all tests
 - dashboard: style filter display in calista pages
 - site: handle redirects between sites and master
 - site: node published state per site
 - site: restore node reference/dereference actions with a better UI
 - site: restore SSO
 - ucms_seo

## Additional

 - all: replace voters by more flexible ACL system (to be determined)
 - all: Rework UI
 - dashboard: icons in admin (provide theme and skin)
 - ONLY UI - ucms_group
 - site: make site state transition matrix configurable
 - site: restore favicon feature
 - ucms_search
 - ucms_taxo
 - ucms_user
 - ucms_widget

## To be done differently

 - [x] DELETED - ucms_debug
 - [x] DELETED - ucms_label
 - ONLY UI - ucms_harm -> ucms_corporate
 - ucms_extranet
 - ucms_layout
 - ucms_list
 - ucms_notification
