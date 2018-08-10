
import { initMenuWidgetOn } from "./components/menu";
import * as Tree from "./tree";

declare const Drupal: any;

Drupal.behaviors.ucmsTreeEdit = {
    attach: (context: Element, settings: any) => {
        for (let element of <HTMLInputElement[]><any>context.querySelectorAll(`[data-menu-tree-edit]`)) {
            const id = <number>(element.getAttribute("data-menu-tree-edit") || 0);
            console.log("found one !");
            Tree.loadTree(id).then(result => initMenuWidgetOn(element, id, result.tree)).catch(err => console.log(err));
        }
    }
};
