
import * as React from "react";
import * as ReactDOM from "react-dom";
import { MenuTreeItem, postTree } from "../tree";
import SortableTree, { TreeItem } from 'react-sortable-tree';

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
        postTree(this.props.menuId, this.state.treeData).then(() => {}).catch((err: any) => console.log(err));
        /*
        this.props.onUpdate(this.state.defaults); // Restore defaults
        this.setState({dialogOpened: false, values: this.state.defaults});
        this.refresh();
         */
    }

    // @todo
    //   - make height computed on front dynamically
    //   - translations
    render() {
        return (
            <div style={{ height: 1000 }}>
                <SortableTree
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

export function initMenuWidgetOn(element: Element, menuId: number, items: MenuTreeItem[]) {
    ReactDOM.render(<MenuWidget menuId={menuId} items={items}/>, element);
}
