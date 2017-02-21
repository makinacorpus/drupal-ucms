# Seo alias migration

Search regexes.

## Various

    SeoAliasStorage|ucms_seo_alias|ensureSitePrimaryAliases|ensureNodePrimaryAlias|getAliasStorage|AliasCanonicalProcessor|AliasDeleteProcessor|StoreLocatorAliasRebuildProcessor

## Get rid of node alias datasource

    NodeAliasDatasource|nodeAliasList|ucms_seo\.admin\.node_alias_datasource|/seo-aliases

## Todo list

 -  [x] drop node alias datasource completely
 -  [x] protect the whole load/recompute/save algorithm using transactions and retry in a transaction; it'll be much safer
 -  caching at some point (in manager?)
 -  handle alias deduplicate
 -  handle invalidation
 -  implement the redirection on hook_menu_status_alter() in case of a 404 not/found with a simple select query
 -  invalidation could use the umenu menu id to be less intensive
 -  last but not least, we need a way to trick drupal path alias manager that our aliases are valid aliases for himself, would be good not to rely upon the path alias manager
 -  rewrite all unit tests and new ones
 -  rewrite service locator url generator
 -  rewrite site alias datasource
 -  url outbound alter (using drupal path alias or not?)
 -  write node current alias and redirect count in node info and form
