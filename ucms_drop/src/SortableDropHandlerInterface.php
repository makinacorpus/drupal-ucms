<?php

namespace MakinaCorpus\Ucms\Drop;

/**
 * Sortable is a drop zone that literally contains items, which can be
 * moved inside.
 *
 * When calling DropHandlerInterface::drop() without a position what happens
 * from there is at the implementation discretion, but the DropStatus instance
 * returned should not leave any doubt about what to do in the UI.
 */
interface SortableDropHandlerInterface extends DropHandlerInterface
{
    /**
     * An item is dropped into a drop zone at a certain position
     *
     * @param scalar $id
     *   Drop zone identifier
     * @param int $position
     *   Position the target is dropped into the drop zone
     * @param DragItem $target
     *   Draggable item
     * @param array $options
     *   Arbitrary set of options from the drop action
     *
     * @return DropStatus
     */
    public function dropAt($id, $position, DragItem $target, $options = []);

    /**
     * And item at a position has been dragged out from a drop zone
     *
     * @param scalar $id
     *   Drop zone identifier
     * @param int $position
     *   Position the target has been dragged out from the drop zone
     * @param DragItem $target
     *   Draggable item
     *
     * @return DropStatus
     */
    public function removeAt($id, $position, DragItem $target);

    /**
     * An item has been moved within the drop zone
     *
     * @param scalar $id
     *   Drop zone identifier
     * @param int $from
     *   Position the target has been dragged out from the drop zone
     * @param int $to
     *   Position the target is dropped into the drop zone
     * @param DragItem $target
     *   Draggable item
     * @param array $options
     *   Arbitrary set of options from the drop action
     *
     * @return DropStatus
     */
    public function move($id, $from, $to, DragItem $target, $options = []);
}
