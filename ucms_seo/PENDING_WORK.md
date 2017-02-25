# Seo alias migration

Search regexes.

## Various

    SeoAliasStorage|ucms_seo_alias|ensureSitePrimaryAliases|ensureNodePrimaryAlias|getAliasStorage|AliasCanonicalProcessor|AliasDeleteProcessor|StoreLocatorAliasRebuildProcessor

## Todo list

 -  [postponed] build a batch to allow user force the aliases warmup
 -  [postponed] build a cron to rebuild arbitrary outdated aliases
 -  [postponed] find a way to trick drupal path alias manager that our aliases are valid aliases for himself, would be good not to rely upon the path alias manager
 -  [postponed] invalidation could use the umenu menu id to be less intensive
 -  [x] caching of current route aliases
 -  [x] drop node alias datasource completely
 -  [x] fix 403 on redirect delete
 -  [x] fix redirect delete action processor to use Redirect class
 -  [x] fix redirect node listing (missing site)
 -  [x] fix redirect site listing (missing node)
 -  [x] implement the redirection on hook_deliver_callback_alter() in case of a 404 not/found with a simple select query
 -  [x] protect the whole load/recompute/save algorithm using transactions and retry in a transaction; it'll be much safer
 -  [x] rewrite site alias datasource
 -  [x] write node current alias in node info and form, allow modification
 -  add query parameters (excluding q) as part of the cache alias lookup cache key
 -  handle alias deduplicate
 -  handle invalidation
 -  rewrite all unit tests and new ones
 -  rewrite service locator url generator (need one from a specific project)
