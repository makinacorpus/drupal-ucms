
import { initMenuWidgetOn } from "./components/menu";

declare var Drupal: any;

Drupal.behaviors.ucmsTreeEdit = {
    attach: (context: Element, settings: any) => {
        for (let element of <HTMLInputElement[]><any>context.querySelectorAll(`[data-menu-tree-edit]`)) {
            initMenuWidgetOn(element, (element.getAttribute("data-menu-tree-edit") || 0) as number);
        }
    }
};
