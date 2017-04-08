# µCMS - Compositon module

This module provides a cleaner, faster and much more flexible dropin replacement
for the *ucms_layout* module.

This module provides a basic upgrade path from the *ucms_layout* module but it
might not fit to everyone's need, that's why it is provided as a Symfony command
rather than a Drupal update function.

# Migrating from Layout

In order to migrate your data from the layout module, you must use the
``ucms:composition:migate`` Symfony command.

There is no automatic upgrade path because both modules work very differently
and you might want to change the data model as you were using it.

Whereas the layout module is heavily based upon regions, the composition module
considers that a single content region is enough, and let the user build its own
column layout by himself. Nevertheless, you can use it the same way layout works
by migrating layout regions to the composition data.

Because of the very different approaches, different migration choices are given
to you:

 * you can migrate all the layout data into a single content region, but you
   will loose the current layout and will need to rebuild it manually for each
   node;

 * you can migrate all the layout data keeping the exact same regions that are
   configured in the layout data, front rendering will therefore remain the
   same (at the exception that the composition module will add bootstrap
   containers and rows in your regions);

 * you might pick the regions you want to keep, and give a region merge plan
   at migration time, allowing you to reduce some regions by merging them
   while keeping some others.

# Status

## Todolist

 * [_] handle home page upon its creation
 * [_] handle homepage-only region (creation, load on every page)
 * [_] php-layout library: bootstrap renderer columns width management
 * [_] php-layout library: bootstrap renderer helpers arguments Container instead of string
 * [_] plug a dashboard selection screen for item selection
 * [_] unit test the event subscriber

## Postponed

 * [_] drag'n'drop from cart
 * [_] phplayout: "set content here" cannot be ajaxified (needs in page modifications)
 * [_] phplayout: add dragula drag'n'drop for moving horizontal containers
 * [_] phplayout: add options form for containers
 * [_] phplayout: ajaxify all action links

## Done

 * [x] handle display
 * [x] migrate from layout
 * [x] phplayout: add dragula drag'n'drop for moving items
 * [x] phplayout: add options form for items
 * [x] phplayout: add style selector for items
 * [x] phplayout: bootstrap: checkbox for container width (fluid or not) or no container
 * [x] phplayout: content zone as block for layout upon region content
 * [x] phplayout: decorelate loaded context from page context
 * [x] phplayout: event for collecting page layout instead of hardcoded query
 * [x] phplayout: parameter for displaying or not edit form in content
 * [x] phplayout: top level per-region options

