# Seo alias migration

Une bonne grosse regex pour mon Eclipse:

    SeoAliasStorage|ucms_seo_alias|ensureSitePrimaryAliases|ensureNodePrimaryAlias|getAliasStorage|AliasCanonicalProcessor|AliasDeleteProcessor|NodeAliasDatasource|StoreLocatorAliasRebuildProcessor

## Todo list

 -  [x] protect the whole load/recompute/save algorithm using transactions
    and retry in a transaction; it'll be much safer
 -  implement the redirection on hook_menu_status_alter() in case
    of a 404 not/found with a simple select query
 -  handle invalidation
 -  invalidation could use the umenu menu id to be less intensive
 -  handle alias deduplicate
 -  url outbound alter (using drupal path alias or not?)
 -  caching at some point (in manager?)
 -  rewrite site alias datasource
 -  drop node alias datasource completely
 -  write node current alias and redirect count in node info and form
 -  rewrite service locator url generator
 -  last but not least, we need a way to trick drupal path alias
    manager that our aliases are valid aliases for himself, would be
    good not to rely upon the path alias manager
 -  rewrite all unit tests and new ones
