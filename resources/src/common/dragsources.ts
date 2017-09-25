/**
 * Everything you ever dreamed of for using drag'n'drop.
 *
 * @todo this should be moved out into the Dragula module.
 */
namespace DragSources {

    export const DATA_BUNDLES = "data-allowed-bundles";
    export const DATA_ITEM_BUNDLE = "data-item-bundle";
    export const DATA_ITEM_ID = "data-item-id";
    export const DATA_ITEM_TYPE = "data-item-type";
    export const SELECTOR_SOURCES = `[data-layout-source="1"]`;

    /**
     * Represents a potential source
     */
    export class Source {
        readonly element: Element;

        /**
         * Default constructor
         */
        constructor(element: Element) {
            this.element = element;
        }
    }

    /**
     * Find all potential sources in document, in order to make it
     * easier for everybody, we re-use the phplayout and ucms sources
     * so it'll work globally transparently.
     */
    export function findAllSourcesInDocument(): Source[] {
        const sources: Source[] = [];

        for (let element of <any>document.querySelectorAll(SELECTOR_SOURCES)) {
            sources.push(new Source(element));
        }

        return sources;
    }

    /**
     * Find all potential sources in context, in order to make it
     * easier for everybody, we re-use the phplayout and ucms sources
     * so it'll work globally transparently.
     */
    export function findAllSourcesIn(context: Document | Element): Source[] {
        const sources: Source[] = [];

        for (let element of <any>context.querySelectorAll(SELECTOR_SOURCES)) {
            sources.push(new Source(element));
        }

        return sources;
    }

    /**
     * Check if item matches the spec, if nothing given for a spec, only
     * ensures that the item is an item
     */
    export function elementMatches(element: Element, itemType?: string, allowedBundles?: string[]): boolean {
        if (!element.hasAttribute(DATA_ITEM_ID) || !element.hasAttribute(DATA_ITEM_TYPE)) {
            return false;
        }

        if (itemType) {
            if (itemType !== element.getAttribute(DATA_ITEM_TYPE)) {
                return false;
            }
        }

        if (allowedBundles) {
            if (!element.hasAttribute(DATA_ITEM_BUNDLE)) {
                return false;
            }
            const bundle = element.getAttribute(DATA_ITEM_BUNDLE);
            if (bundle) {
                return -1 !== allowedBundles.indexOf(bundle);
            }
            return false;
        }

        return true;
    }
}
