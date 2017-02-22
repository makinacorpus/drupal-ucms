# Seo alias migration

Search regexes.

## Various

    SeoAliasStorage|ucms_seo_alias|ensureSitePrimaryAliases|ensureNodePrimaryAlias|getAliasStorage|AliasCanonicalProcessor|AliasDeleteProcessor|StoreLocatorAliasRebuildProcessor

## Get rid of node alias datasource

    NodeAliasDatasource|nodeAliasList|ucms_seo\.admin\.node_alias_datasource|/seo-aliases

## Todo list

 -  [postponed] build a batch to allow user force the aliases warmup
 -  [postponed] build a cron to rebuild arbitrary outdated aliases
 -  [postponed] caching at some point (in manager?)
 -  [postponed] invalidation could use the umenu menu id to be less intensive
 -  [x] drop node alias datasource completely
 -  [x] fix 403 on redirect delete
 -  [x] fix redirect delete action processor to use Redirect class
 -  [x] implement the redirection on hook_deliver_callback_alter() in case of a 404 not/found with a simple select query
 -  [x] protect the whole load/recompute/save algorithm using transactions and retry in a transaction; it'll be much safer
 -  fix redirect node listing (missing site)
 -  fix redirect site listing (missing node)
 -  handle alias deduplicate
 -  handle invalidation
 -  last but not least, we need a way to trick drupal path alias manager that our aliases are valid aliases for himself, would be good not to rely upon the path alias manager
 -  rewrite all unit tests and new ones
 -  rewrite RedirectStorage to pass a Redirect object to hooks, and return this object
 -  rewrite service locator url generator
 -  rewrite site alias datasource
 -  url outbound alter (using drupal path alias or not?)
 -  write node current alias and redirect count in node info and form
