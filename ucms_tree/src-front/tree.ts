
import { TreeItem } from 'react-sortable-tree';

/*
export interface Menu {
    description?: string;
    id: number;
    is_main?: boolean;
    name: string;
    role?: string;
    site_id: string;
    title?: string;
}

export interface MenuItem {
    description: string;
    id: number;
    menu_id: number;
    node_id: number;
    parent_id: number;
    site_id: number;
    title: string;
    weight: number;
}
 */

export interface MenuTreeItem extends TreeItem {
    id: number;
}

export interface TreeResult {
    tree: MenuTreeItem[];
}

export function postTree(id: number, tree: TreeItem[]): Promise<void> {
    return new Promise<void>((resolve: () => void, reject: (err: any) => void) => {
        try {
            const req = new XMLHttpRequest();
            //const data = new FormData();
            //data.append("tree", JSON.stringify(tree));
            req.open('POST', '/admin/structure/ajax/tree/' + id);
            req.setRequestHeader("Content-Type", "application/json" );
            req.addEventListener("load", () => {
                if (req.status !== 200) {
                    reject(`${req.status}: ${req.statusText}: ${req.responseText}`);
                } else {
                    resolve();
                }
            });
            req.addEventListener("error", () => {
                reject(`${req.status}: ${req.statusText}: ${req.responseText}`);
            });
            req.send(/* data */ JSON.stringify({tree: tree}));
        } catch (err) {
            reject(err);
        }
    });
}

export function loadTree(id: number): Promise<TreeResult> {
    return new Promise<TreeResult>((resolve: (result: TreeResult) => void, reject: (err: any) => void) => {
        const req = new XMLHttpRequest();
        req.open('GET', '/admin/structure/ajax/tree/' + id);
        req.setRequestHeader("Accept", "application/json" );
        req.addEventListener("load", () => {
            if (req.status !== 200) {
                reject(`${req.status}: ${req.statusText}: ${req.responseText}`);
            } else {
                try {
                    const result = <TreeResult>JSON.parse(req.responseText);
                    // Populate mandatory parameters to ensure that the caller
                    // will always have something to NOT crash upon.
                    if (!result.tree) {
                        result.tree = [];
                    }
                    resolve(result);
                } catch (error) {
                    reject(`${req.status}: ${req.statusText}: cannot parse JSON: ${error}`);
                }
            }
        });
        req.addEventListener("error", () => {
            reject(`${req.status}: ${req.statusText}: ${req.responseText}`);
        });
        req.send();
    });
}
