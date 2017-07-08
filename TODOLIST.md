# Todo-list

## Before releasing stable

### API improvements

 * [ ] add a complete drag and drop source and item handling api

### Integration

 * [ ] add select2 less version for theming
 * [ ] move drag and drop source and item handling to dragula module
 * [ ] remove select2 dynamic bootstrap theme selection
 * [ ] rewrite all JavaScript to TypeScript
 * [ ] rewrite ckeditor plugin to use dragula for drag and drop
 * [ ] rewrite contrib.less to use variables
 * [ ] rewrite tree.less to use variables
 * [ ] split contrib.less, move items theming in calista

### Bugfixes

 * [ ] finish ucms_composition module, see ucms_composition/README.md
 * [ ] fix ucms_tree restore max depth for menus
 * [ ] fix calista and elastic, facets cannot be selected
 * [ ] move tests to their own folders
 * [ ] rewrite ucms_layout code to dragula (currently is removed)
 * [x] fix missing ucms_tree add button in tree admin
 * [x] fix redirection on ucms_tree add
 * [x] fix ucms_tree added root elements position is not saved

### Migration and testing

 * [ ] ensure upgrade path from previous versions
 * [ ] test ucms_extranet module
 * [x] test ucms_group module

### Calista migration

 * [ ] contrib, search: move elastic datasource in the search module
 * [ ] normalize acl/access usage via isGranted and voters
 * [ ] rewrite calista JavaScript as TypeScript
 * [x] admin table event name still conflict with udashboard
 * [x] contrib, search: move elastic datasource in the search module
 * [x] contrib: fix admin page registration (make it easy once again)
 * [x] group: add missing dynamic parameters in base queries
 * [x] migrate all pages to new version
 * [x] restore all portlets

## Long term

 * [ ] allow content to be created in dialogs (fast creation, not all options)
 * [ ] deprecated then drop ucms_layout in a future version
 * [ ] investigate plugging calista to a front framework too
 * [ ] investigate potential front framework for admin UI
 * [ ] make *every* module optional
 * [ ] rewrite search module to be less complex
 * [ ] rewrite ucms_label to be a much more flexible front for taxonomy
 * [ ] theme breadcrumb in toolbar
 * [x] move breadcrumb to somewhere it takes less space (toolbar?)

## Decoupling

 * [ ] move cart ot its own module, usable without ucms
 * [ ] move contrib into its own module, usable without ucms
 * [ ] move non ucms related node actions to their own module
