# Pending port to Drupal 8.x

## Minimal viable product

 - [x] DELETED - site: restore post init event
 - [x] FIXED - all: fixe destination parameter usage
 - [x] FIXED - dashboard: bug in calista search (search parameter override itself)
 - [x] FIXED - Modern front assets toolchain
 - [x] FIXED - sf-int: write a proxy to router access check system to symfony authorization checker
 - [x] FIXED - site: finish site listing screen
 - [x] FIXED - site: get rid once for all of Drupal role to site role relationship
 - [x] FIXED - site: plug ucms_site on umenu using MenuEnvEvent to set site_id condition
 - [x] FIXED - site: restore admin theme on admin paths when in site
 - [x] FIXED - site: restore menu site status alteration
 - [x] FIXED - site: restore site access query alter
 - [x] FIXED - tree: force node attach to site on item form and multiple item form
 - [x] HARDCODED - site: restore state transition matrix
 - [x] PORTED - site: prevent accessing non-handled hostnames
 - [x] PORTED - site: restore node access query alter
 - [x] PORTED - ucms_dashboard
 - [x] PORTED - ucms_site
 - [x] PORTED - ucms_tree - Needed in order to port SEO module and to finish site module port
 - [x] PORTED - umenu
 - all: restore SQL constraints
 - cache: all: handle caching gracefully
 - cache: site: clear node cache on reference operations
 - cache: tree: correct menu block cacheability
 - front: tree: allow item deletion in manage links page
 - front: tree: allow title edit in manage links page
 - site: fix cross url route generator to use path matching
 - site: fix missing site frontpage (home node)

## Necessary

 - [x] DELETED - contrib: cart must die
 - [x] DELETED - contrib: remove custom ckeditor customisations
 - [x] FIXED - all: userCanX() methods must die, then merge NodeAccessService into SiteAccessService
 - all: get rid of PHP_SAPI === 'cli' or drupal_is_cli()
 - all: rewrite all tests
 - dashboard: excessively test actions subsystem
 - dashboard: improve page controller trait
 - front: dashboard: restore/improve seven fixes
 - front: dashboard: style filter display in calista pages
 - front: dashboard: untangle display skin
 - PENDING - site: restore SSO, moving features from ucms_sso in
 - PENDING - ucms_sso
 - site: excessively test site core features and node access
 - site: handle redirects between sites and master
 - site: node published state per site
 - site: restore node reference/dereference actions with a better UI
 - site: when accessing non-handled hostnames, do not use site theme
 - ucms_contrib
 - ucms_seo

## Additional

 - all: replace voters by more flexible ACL system (to be determined)
 - all: Rework UI
 - all: use site in request attributes instead of manager for context
 - contrib: implement media access - disable delete when media in use
 - contrib: make role dynamic (configuration per site)
 - contrib: make role node ACL configurable per site
 - dashboard: icons in admin (provide theme and skin)
 - ONLY UI - ucms_group
 - site: allow sso disabling
 - site: make site state transition matrix configurable
 - site: place current site in request attributes
 - site: restore favicon feature
 - tree: research systray integration for displaying current site navigation
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
