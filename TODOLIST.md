# Todo-list

## Before releasing stable

### API improvements

 * [_] add a complete drag and drop source and item handling api

### Integration

 * [_] add select2 less version for theming
 * [_] move drag and drop source and item handling to dragula module
 * [_] remove select2 dynamic bootstrap theme selection
 * [_] rewrite all JavaScript to TypeScript
 * [_] rewrite ckeditor plugin to use dragula for drag and drop
 * [_] rewrite contrib.less to use variables
 * [_] rewrite tree.less to use variables
 * [_] split contrib.less, move items theming in calista

### Bugfixes

 * [_] finish ucms_composition module, see ucms_composition/README.md
 * [_] fix ucms_tree restore max depth for menus
 * [_] fix calista and elastic, facets cannot be selected
 * [_] move tests to their own folders
 * [_] rewrite ucms_layout code to dragula (currently is removed)
 * [x] fix missing ucms_tree add button in tree admin
 * [x] fix redirection on ucms_tree add
 * [x] fix ucms_tree added root elements position is not saved

### Migration and testing

 * [_] ensure upgrade path from previous versions
 * [_] rewrite ucms_harm module to make it work
 * [_] test ucms_extranet module
 * [_] test ucms_group module

### Calista migration

 * [_] contrib, search: move elastic datasource in the search module
 * [_] group: add missing dynamic parameters in base queries
 * [_] normalize acl/access usage via isGranted and voters
 * [_] restore all portlets
 * [_] rewrite calista JavaScript as TypeScript
 * [x] admin table event name still conflict with udashboard
 * [x] contrib: fix admin page registration (make it easy once again)
 * [x] migrate all pages to new version

## Long term

 * [_] allow content to be created in dialogs (fast creation, not all options)
 * [_] deprecated then drop ucms_layout in a future version
 * [_] investigate plugging calista to a front framework too
 * [_] investigate potential front framework for admin UI
 * [_] make *every* module optional
 * [_] move cart ot its own module, usable without ucms
 * [_] move contrib into its own module, usable without ucms
 * [_] move search (content/media handling) into its own module, usable without ucms
 * [_] rewrite search module to be less complex
 * [_] rewrite ucms_label to be a much more flexible front for taxonomy
 * [_] theme breadcrumb in toolbar
 * [x] move breadcrumb to somewhere it takes less space (toolbar?)
