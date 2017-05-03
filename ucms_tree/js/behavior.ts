
// @todo not sure it meant to be used that way
declare const Drupal: any;

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

        for (let node of <any>context.querySelectorAll(UcmsTree.SELECTOR_WIDGET)) {
            UcmsTree.initializeWidget(node);
        }

        UcmsTree.intializeSources(DragSources.findAllSourcesIn(context));
    }
};
