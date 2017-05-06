
// @todo not sure it meant to be used that way
declare const Drupal: any;

// Drupal attach point
Drupal.behaviors.UcmsTree = {
    attach: function(context: Document | Element | any) {

        // This should not happen, but some Drupal module will attempt
        // attaching items on jQuery selectors instead of DOM elements.
        if (!context.querySelectorAll) {
            if (context.get) {
                context = <Element>context.get(0);
            } else {
                return;
            }
        }

        // Initialize all widgets found in the current context, up to
        // this initialization, they are not attached to any source
        // yet.
        for (let node of <any>context.querySelectorAll(UcmsTree.SELECTOR_WIDGET)) {
            UcmsTree.initializeWidget(node);
        }

        // Each time something was changed by Drupal API, find all sources
        // in the changed context, and allow all the widgets we know to
        // attach to these new sources
        UcmsTree.intializeSources(DragSources.findAllSourcesIn(context));
    }
};
