/**
 * µNodeReference Dragula based code.
 */
namespace UcmsTree {

    export const SELECTOR_ITEM = "[data-mlid]";
    export const SELECTOR_ROOT = "[data-menu]";
    export const SELECTOR_WIDGET = "#ucms-tree-tree-form";

    const liveWidgets: dragula.Drake[] = [];

    /**
     * Default item template
     */
    const TEMPLATE_ITEM = '<li data-item-type="node" data-item-id="__ITEM_ID__">'
        + '<div class="tree-item clearfix">'
        + '<input class="form-control form-text" value="__TITLE__" maxlength="128" type="text"/>'
        + '<span class="glyphicon glyphicon-remove"></span>'
        + '</div>'
        + '<ol class="empty"></ol>'
        + '</li>'
    ;

    /**
     * Attach additional behaviors
     */
    function attachAdditionalItemBehaviors(form: Element, context: Element): void {
        // Attach on change behavior for input fields
        for (let input of <any>context.querySelectorAll("input[type=\"text\"]")) {
            (<HTMLInputElement>input).onchange = () => updateValue(form);
        }
    }

    /**
     * Create new item
     */
    function createItem(form: Element, element: Element, drake: dragula.Drake): Element {
        // Item id is mandatory, we now we have one since the item
        // was actually acceptable by dragula
        const itemId = <string>element.getAttribute(DragSources.DATA_ITEM_ID);

        // Attempt to dynamically find a title
        let title: string = "";
        for (let tagName of ["h1", "h2", "h3", "h4"]) {
            const node = element.querySelector(tagName);
            if (node && node.textContent) {
                title = <string>node.textContent;
                break;
            }
        }

        const wrapper = document.createElement('div');
        const text = TEMPLATE_ITEM.replace("__ITEM_ID__", itemId).replace("__TITLE__", title).trim();
        wrapper.innerHTML = text;

        const innerContainer = wrapper.querySelector("ol");
        if (innerContainer) {
            drake.containers.push(innerContainer);
        }

        attachAdditionalItemBehaviors(form, wrapper);

        return <Element>wrapper.childNodes[0];
    }

    /**
     * Update value for form
     */
    function updateValue(form: Element) {

        const hiddenField = form.querySelector("input[name=\"values\"]");
        if (!hiddenField) {
            throw "Cannot update form state";
        }

        // Find root
        const root = form.querySelector("ol.sortable");
        if (!root) {
            throw "Could not find menu root";
        }

        var increment = 0;
        const value: any[] = [];

        // Traverse traverse all children recursively
        const recurse = (parent: Element, parentId: string) => {
            for (let child of <any>parent.childNodes) {
                if (child instanceof Element && DragSources.elementMatches(child, "node")) {

                    // Find item title
                    const titleNode = child.querySelector("input[type=\"text\"]");
                    let title: string;
                    if (titleNode instanceof HTMLInputElement) {
                        title = titleNode.value;
                    } else {
                        title = "";
                    }
                    const itemType = <string>child.getAttribute(DragSources.DATA_ITEM_TYPE);
                    const itemId = <string>child.getAttribute(DragSources.DATA_ITEM_ID);

                    // Find parent identifier, if none force one
                    let id = child.getAttribute("data-mlid");
                    // Reattribute a new identifier to previously created items
                    if (!id || "new_" === id.substr(0, 4)) {
                        increment = increment + 1;
                        id = "new_" + increment;
                        child.setAttribute("data-mlid", id);
                    }

                    // Push new value and proceed
                    value.push(<any>{
                        item_type: itemType,
                        item_id: itemId,
                        parent_id: parentId,
                        id: id,
                        title: title
                    });

                    const sub = child.querySelector("ol");
                    if (sub) {
                        recurse(sub, id);
                    }
                }
            }
        };

        recurse(root, "");

        hiddenField.setAttribute("value", JSON.stringify(value));
    }

    /**
     * Sources have potentially been added to the DOM
     */
    export function intializeSources(sources: DragSources.Source[]): void {
        if (!liveWidgets.length) {
            return;
        }
        // No sources mean that the widgets cannot work.
        if (!sources.length) {
            return;
        }

        for (let source of sources) {
            const element = source.element;
            if (element.hasAttribute("data-tree-enabled")) {
                continue;
            }
            element.setAttribute("data-tree-enabled", "1");
            for (let drake of liveWidgets) {
                drake.containers.push(element);
            }
        }
    }

    /**
     * Initialiaze a single widget
     */
    export function initializeWidget(form: Element): void {

        const sources: Element[] = [];

        var root = <Element>form.querySelector(SELECTOR_ROOT);
        if (!root) {
            throw "Could not find root item";
        }

        for (let item of <any>form.querySelectorAll(SELECTOR_ITEM)) {
            // Find inner droppable
            let innerContainer = <Element>(<Element>item).querySelector("ol");

            // Add an empty zone for dropping if there's no child
            if (!innerContainer) {
                innerContainer = document.createElement('ol');
                innerContainer.setAttribute("class", "empty");
                (<Element>item).appendChild(innerContainer);
            }

            sources.push(innerContainer);
        }
        sources.push(root);

        // Attach original behaviors
        attachAdditionalItemBehaviors(form, form);

        // Populate initial array else any form submission would result in
        // potential data loss for non empty trees
        updateValue(form);

        const drake = dragula(sources, {
            copy: (element: Element, source: Element) => "ol" !== source.tagName.toLowerCase(),
            accepts: (element: Element, target: Element) => "ol" === target.tagName.toLowerCase() && DragSources.elementMatches(element, "node"),
            // invalid: (element: Element) => !$(element).closest('[data-item-type]').length,
            revertOnSpill: true,
            removeOnSpill: false,
            direction: 'vertical'
        });

        drake.on("drop", (element: Element, target: Element, source: Element) => {
            if ("ol" === source.tagName.toLowerCase()) {
                // If source is empty, add the "empty" class onto the ol
                if (!source.childElementCount) {
                    source.classList.add("empty");
                }
            } else {
                const item = createItem(form, element, drake);
                if (element.parentNode) {
                    element.parentNode.replaceChild(item, element);
                }
            }
            // In all cases, if the target was empty, remove the empty class
            target.classList.remove("empty");
            // Update the hidden field
            updateValue(form);
        });

        liveWidgets.push(drake);
    }
}
