<?php

namespace MakinaCorpus\Ucms\Drop;

interface DropHandlerInterface
{
    /**
     * Get object type
     *
     * @return string
     */
    public function getType();

    /**
     * Can this drop handler receive this object
     *
     * @param scalar $id
     *   Drop zone identifier
     * @param DragItem $target
     *   Draggable item
     *
     * @return boolean
     */
    public function accepts($id, DragItem $target);

    /**
     * An item is dropped into a drop zone
     *
     * @param scalar $id
     *   Drop zone identifier
     * @param DragItem $target
     *   Draggable item
     * @param array $options
     *   Arbitrary set of options from the drop action
     *
     * @return DropStatus
     */
    public function drop($id, DragItem $target, $options = []);

    /**
     * And item has been dragged out from a drop zone
     *
     * @param scalar $id
     *   Drop zone identifier
     * @param DragItem $target
     *   Draggable item
     *
     * @return DropStatus
     */
    public function remove($id, DragItem $target);

    /**
     * Render the item into this zone it has been dropped
     *
     * @param scalar $id
     *   Drop zone identifier
     * @param DragItem $target
     *   Draggable item
     * @param array $options
     *   Arbitrary set of options from the drop action
     *
     * @return string
     */
    public function render($id, DragItem $target, $options = []);
}
