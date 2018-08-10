
import * as React from "react";
import * as ReactDOM from "react-dom";
import SortableTree, { TreeItem } from 'react-sortable-tree';

interface MenuTreeItem extends TreeItem {
    id: number;
}

interface TreeResult {
    tree: MenuTreeItem[];
}

type TreeResultResolve = (result: TreeResult) => void;

type ErrorReject = (reason?: any) => void;

interface MenuWidgetProps {
    readonly menuId: number;
    readonly items: MenuTreeItem[];
};

interface MenuWidgetState {
    readonly treeData: TreeItem[];
};

export class MenuWidget extends React.Component<MenuWidgetProps, MenuWidgetState> {

    constructor(props: MenuWidgetProps) {
        super(props);

        this.state = {
            treeData: this.props.items
        };

        this.onResetClick = this.onResetClick.bind(this);
        this.onSaveClick = this.onSaveClick.bind(this);
    }

    private onResetClick(event: React.MouseEvent<HTMLButtonElement>) {
        event.preventDefault();
        this.setState({treeData: this.props.items});
    }

    private onSaveClick(event: React.MouseEvent<HTMLButtonElement>) {
        event.preventDefault();
        postTree(this.props.menuId, this.state.treeData)
            .then((result) => this.setState({treeData: result.tree}))
            .catch((err: any) => console.log(err))
        ;
    }

    // @todo
    //   - make height computed on front dynamically
    //   - translations
    render() {
        return (
            //<div style={{ height: 1000 }}>
            <div>
                <SortableTree
                    isVirtualized={false}
                    onChange={treeData => this.setState({ treeData })}
                    treeData={this.state.treeData}
                />
                <div>
                    <button className="button btn btn-primary" onClick={this.onResetClick}>
                        Reset
                    </button>
                    <button className="button btn btn-primary" onClick={this.onSaveClick}>
                        Save
                    </button>
                </div>
            </div>
        );
    }
}

export function initMenuWidgetOn(element: Element, id: number) {
    loadTree(id)
        .then(result => ReactDOM.render(<MenuWidget menuId={id} items={result.tree}/>, element))
        .catch(err => console.log(err))
    ;
}

/**
 * Post new version of tree, resolve to the new Tree result.
 */
function postTree(id: number, tree: TreeItem[]): Promise<TreeResult> {
    return new Promise<TreeResult>((resolve: TreeResultResolve, reject: ErrorReject) => {
        try {
            const req = new XMLHttpRequest();
            req.open('POST', '/admin/structure/ajax/tree/' + id);
            req.setRequestHeader("Content-Type", "application/json" );
            req.addEventListener("load", () => {
                if (req.status !== 200) {
                    reject(`${req.status}: ${req.statusText}: ${req.responseText}`);
                } else {
                    try {
                        const result = JSON.parse(req.responseText) as TreeResult;
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
            req.send(JSON.stringify({tree: tree}));
        } catch (err) {
            reject(err);
        }
    });
}

/**
 * Load tree.
 */
function loadTree(id: number): Promise<TreeResult> {
    return new Promise<TreeResult>((resolve: TreeResultResolve, reject: ErrorReject) => {
        const req = new XMLHttpRequest();
        req.open('GET', '/admin/structure/ajax/tree/' + id);
        req.setRequestHeader("Accept", "application/json" );
        req.addEventListener("load", () => {
            if (req.status !== 200) {
                reject(`${req.status}: ${req.statusText}: ${req.responseText}`);
            } else {
                try {
                    const result = JSON.parse(req.responseText) as TreeResult;
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
