# Seo alias

The current module is the second major rewrite of the SEO path alias handling.

Features are:

 *  it does not rely on a Drupal alias table anymore;
 *  a node, per site, can have one and only one alias;
 *  this alias can be protected, this means never regenerated anymore: this
    allows site administrator to write custom hardcoded path aliases;
 *  on each alias regeneration or manual change, the old alias will be saved
    in the redirect table with an expiry date.

## Todo list

 -  [pending] rewrite all unit tests and new ones
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
 -  [x] handle alias deduplicate
 -  [x] handle invalidation
 -  [x] implement the redirection on hook_deliver_callback_alter() in case of a 404 not/found with a simple select query
 -  [x] protect the whole load/recompute/save algorithm using transactions and retry in a transaction; it'll be much safer
 -  [x] rewrite site alias datasource
 -  [x] write node current alias in node info and form, allow modification
 -  add query parameters (excluding q) as part of the cache alias lookup cache key
 -  rewrite service locator url generator (need one from a specific project)
