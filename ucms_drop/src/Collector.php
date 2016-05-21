<?php

namespace MakinaCorpus\Ucms\Drop;

/**
 * This class will be a registered service that collects everything being
 * displayed on the screen upon initialization, it will build and acceptation
 * matrix so that the JavaScript may drive the user without doing AJAX requests
 * for everything; even though using the AJAX API remains the only safe way to
 * determine droppable state.
 *
 * Per default, the JavaScript needs a way to find elements on the screen, for
 * this we provide either of the following two methods:
 *
 *  - set and identifier in the collect() or register() method, and leave this
 *    for the JavaScript to find it, beware that the identifier must be unique
 *    even if the same draggable is present more than once in the HTML page;
 *
 *  - do not set nothing, but provide in your HTML rendered code the following
 *    data attributes:
 *
 *      - data-droppable-type and data-droppable-id for a droppable, if any of
 *        the two is missing, the droppable will not be initialized and remain
 *        unseen by the JavaScript API;
 *
 *      - data-draggable-type and data-draggable-id for a draggable, same
 *        warning applies.
 *
 * If you don't explicitely collect or register your draggables and dropppables
 * but set the needed data attributes, the API will work seamlessly using only
 * AJAX requests to determine object states.
 */
class Collector
{
    /**
     * @var DragItem[]
     */
    private $draggables = [];

    /**
     * @var DropHandlerInterface[]
     */
    private $droppables = [];

    /**
     * Collect a draggable
     *
     * @param DragItem $draggable
     * @param string $identifier
     */
    public function collect(DragItem $draggable, $identifier = null)
    {
        if (!$identifier) {
            $identifier = $draggable->getType() . ':' . $draggable->getId();
        }

        $this->draggables[$identifier] = $draggable;
    }

    /**
     * Collect a droppable
     *
     * @param DropHandlerInterface $droppable
     */
    public function register(DropHandlerInterface $droppable, $identifier = null)
    {
        if (!$identifier) {
            $identifier = $droppable->getType();
        }

        $this->droppables[$identifier] = $droppable;
    }
}
