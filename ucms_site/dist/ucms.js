"use strict";
Drupal.behaviors.UcmsInit = {};
var DragSources;
(function (DragSources) {
    DragSources.DATA_BUNDLES = "data-allowed-bundles";
    DragSources.DATA_ITEM_BUNDLE = "data-item-bundle";
    DragSources.DATA_ITEM_ID = "data-item-id";
    DragSources.DATA_ITEM_TYPE = "data-item-type";
    DragSources.SELECTOR_SOURCES = "[data-layout-source=\"1\"]";
    var Source = (function () {
        function Source(element) {
            this.element = element;
        }
        return Source;
    }());
    DragSources.Source = Source;
    function findAllSourcesInDocument() {
        var sources = [];
        for (var _i = 0, _a = document.querySelectorAll(DragSources.SELECTOR_SOURCES); _i < _a.length; _i++) {
            var element = _a[_i];
            sources.push(new Source(element));
        }
        return sources;
    }
    DragSources.findAllSourcesInDocument = findAllSourcesInDocument;
    function findAllSourcesIn(context) {
        var sources = [];
        for (var _i = 0, _a = context.querySelectorAll(DragSources.SELECTOR_SOURCES); _i < _a.length; _i++) {
            var element = _a[_i];
            sources.push(new Source(element));
        }
        return sources;
    }
    DragSources.findAllSourcesIn = findAllSourcesIn;
    function elementMatches(element, itemType, allowedBundles) {
        if (!element.hasAttribute(DragSources.DATA_ITEM_ID) || !element.hasAttribute(DragSources.DATA_ITEM_TYPE)) {
            return false;
        }
        if (itemType) {
            if (itemType !== element.getAttribute(DragSources.DATA_ITEM_TYPE)) {
                return false;
            }
        }
        if (allowedBundles) {
            if (!element.hasAttribute(DragSources.DATA_ITEM_BUNDLE)) {
                return false;
            }
            var bundle = element.getAttribute(DragSources.DATA_ITEM_BUNDLE);
            if (bundle) {
                return -1 !== allowedBundles.indexOf(bundle);
            }
            return false;
        }
        return true;
    }
    DragSources.elementMatches = elementMatches;
})(DragSources || (DragSources = {}));
Drupal.behaviors.UcmsTree = {
    attach: function (context) {
        if (!context.querySelectorAll) {
            if (context.get) {
                context = context.get(0);
            }
            else {
                return;
            }
        }
        for (var _i = 0, _a = context.querySelectorAll(UcmsTree.SELECTOR_WIDGET); _i < _a.length; _i++) {
            var node = _a[_i];
            UcmsTree.initializeWidget(node);
        }
        UcmsTree.intializeSources(DragSources.findAllSourcesIn(context));
    }
};
var UcmsTree;
(function (UcmsTree) {
    UcmsTree.SELECTOR_ITEM = "[data-mlid]";
    UcmsTree.SELECTOR_ROOT = "[data-menu]";
    UcmsTree.SELECTOR_WIDGET = "#ucms-tree-tree-form";
    var liveWidgets = [];
    var TEMPLATE_ITEM = '<li data-item-type="node" data-item-id="__ITEM_ID__">'
        + '<div class="tree-item clearfix">'
        + '<input class="form-control form-text" value="__TITLE__" maxlength="128" type="text"/>'
        + '<span class="fa fa-times"></span>'
        + '</div>'
        + '<ol class="empty"></ol>'
        + '</li>';
    function attachAdditionalItemBehaviors(form, context) {
        for (var _i = 0, _a = context.querySelectorAll("input[type=\"text\"]"); _i < _a.length; _i++) {
            var input = _a[_i];
            input.onchange = function () { return updateValue(form); };
        }
        var _loop_1 = function (span) {
            span.onclick = function () {
                var search = span;
                while (search.parentElement) {
                    search = search.parentElement;
                    if ("li" === search.tagName.toLowerCase()) {
                        if (search.parentElement) {
                            search.parentElement.removeChild(search);
                        }
                        updateValue(form);
                        return;
                    }
                }
            };
        };
        for (var _b = 0, _c = context.querySelectorAll("span.fa-times"); _b < _c.length; _b++) {
            var span = _c[_b];
            _loop_1(span);
        }
    }
    function createItemClone(element) {
        var itemId = element.getAttribute(DragSources.DATA_ITEM_ID);
        var title = "";
        for (var _i = 0, _a = ["h1", "h2", "h3", "h4"]; _i < _a.length; _i++) {
            var tagName = _a[_i];
            var node = element.querySelector(tagName);
            if (node && node.textContent) {
                title = node.textContent;
                break;
            }
        }
        var wrapper = document.createElement('div');
        var text = TEMPLATE_ITEM.replace("__ITEM_ID__", itemId).replace("__TITLE__", title).trim();
        wrapper.innerHTML = text;
        return wrapper.childNodes[0];
    }
    function createItem(form, element, drake) {
        var item = createItemClone(element);
        var innerContainer = item.querySelector("ol");
        if (innerContainer) {
            drake.containers.push(innerContainer);
        }
        attachAdditionalItemBehaviors(form, item.parentElement ? item.parentElement : item);
        item.setAttribute("data-tree-transformed", "1");
        item.classList.add("tree-new");
        return item;
    }
    function isTarget(container) {
        return "ol" === container.tagName.toLowerCase();
    }
    function updateValue(form) {
        var hiddenField = form.querySelector("input[name=\"values\"]");
        if (!hiddenField) {
            throw "Cannot update form state";
        }
        var root = form.querySelector("ol.sortable");
        if (!root) {
            throw "Could not find menu root";
        }
        var increment = 0;
        var value = [];
        var recurse = function (parent, parentId) {
            for (var _i = 0, _a = parent.childNodes; _i < _a.length; _i++) {
                var child = _a[_i];
                if (child instanceof Element && DragSources.elementMatches(child, "node")) {
                    if (child.classList.contains("gu-transit")) {
                        continue;
                    }
                    var titleNode = child.querySelector("input[type=\"text\"]");
                    var title = void 0;
                    if (titleNode instanceof HTMLInputElement) {
                        title = titleNode.value;
                    }
                    else {
                        title = "";
                    }
                    var itemType = child.getAttribute(DragSources.DATA_ITEM_TYPE);
                    var itemId = child.getAttribute(DragSources.DATA_ITEM_ID);
                    var id = child.getAttribute("data-mlid");
                    if (!id || "new_" === id.substr(0, 4)) {
                        increment = increment + 1;
                        id = "new_" + increment;
                        child.setAttribute("data-mlid", id);
                    }
                    value.push({
                        item_type: itemType,
                        item_id: itemId,
                        parent_id: parentId,
                        id: id,
                        title: title
                    });
                    var sub = child.querySelector("ol");
                    if (sub) {
                        recurse(sub, id);
                    }
                }
            }
        };
        recurse(root, "");
        hiddenField.setAttribute("value", JSON.stringify(value));
    }
    function intializeSources(sources) {
        if (!liveWidgets.length) {
            return;
        }
        if (!sources.length) {
            return;
        }
        for (var _i = 0, sources_1 = sources; _i < sources_1.length; _i++) {
            var source = sources_1[_i];
            var element = source.element;
            if (element.hasAttribute("data-tree-enabled")) {
                continue;
            }
            element.setAttribute("data-tree-enabled", "1");
            for (var _a = 0, liveWidgets_1 = liveWidgets; _a < liveWidgets_1.length; _a++) {
                var drake = liveWidgets_1[_a];
                drake.containers.push(element);
            }
        }
    }
    UcmsTree.intializeSources = intializeSources;
    function initializeWidget(form) {
        var sources = [];
        var root = form.querySelector(UcmsTree.SELECTOR_ROOT);
        if (!root) {
            throw "Could not find root item";
        }
        for (var _i = 0, _a = form.querySelectorAll(UcmsTree.SELECTOR_ITEM); _i < _a.length; _i++) {
            var item = _a[_i];
            var innerContainer = item.querySelector("ol");
            item.setAttribute("data-tree-transformed", "1");
            if (!innerContainer) {
                innerContainer = document.createElement('ol');
                innerContainer.setAttribute("class", "empty");
                item.appendChild(innerContainer);
            }
            sources.push(innerContainer);
        }
        sources.push(root);
        attachAdditionalItemBehaviors(form, form);
        updateValue(form);
        var drake = dragula(sources, {
            copy: function (element, source) { return "ol" !== source.tagName.toLowerCase(); },
            accepts: function (element, target) { return "ol" === target.tagName.toLowerCase() && DragSources.elementMatches(element, "node"); },
            revertOnSpill: true,
            removeOnSpill: false,
            direction: 'vertical'
        });
        var currentDragElement;
        var currentClone;
        drake.on("drag", function (element, source) {
            currentDragElement = element;
            if (!isTarget(source)) {
                currentClone = createItemClone(element);
                currentClone.classList.add("gu-transit");
            }
        });
        drake.on("dragend", function (element) {
            if (currentDragElement) {
                currentDragElement.removeAttribute("style");
            }
            currentDragElement = null;
            if (currentClone && currentClone.parentNode) {
                currentClone.parentNode.removeChild(currentClone);
            }
            currentClone = null;
        });
        drake.on("over", function (element, container, source) {
            if (isTarget(container) && currentClone && element.parentElement) {
                element.setAttribute("style", "display: none !important;");
                element.parentElement.insertBefore(currentClone, element);
            }
        });
        drake.on("shadow", function (element, container, source) {
            if (isTarget(container) && currentClone && element.parentElement) {
                element.parentElement.insertBefore(currentClone, element);
            }
        });
        drake.on("drop", function (element, target, source) {
            if (isTarget(source)) {
                if (!source.childElementCount) {
                    source.classList.add("empty");
                }
                if (!element.classList.contains("tree-new")) {
                    element.classList.add("tree-modified");
                }
                if (element.classList.contains("gu-transit")) {
                    element.classList.remove("gu-transit");
                }
            }
            else {
                var item = createItem(form, element, drake);
                if (element.parentNode) {
                    element.parentNode.replaceChild(item, element);
                }
            }
            target.classList.remove("empty");
            updateValue(form);
        });
        liveWidgets.push(drake);
    }
    UcmsTree.initializeWidget = initializeWidget;
})(UcmsTree || (UcmsTree = {}));
//# sourceMappingURL=/var/www/chlovet/web/sites/all/modules/composer/drupal-ucms/ucms_site/dist/ucms.js.map