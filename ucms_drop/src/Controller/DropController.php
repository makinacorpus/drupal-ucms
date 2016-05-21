<?php

namespace MakinaCorpus\Ucms\Drop\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Drop\DropHandlerInterface;
use MakinaCorpus\Ucms\Drop\DragItem;
use MakinaCorpus\Ucms\Drop\DropStatus;
use MakinaCorpus\Ucms\Drop\HandlerRegistry;

use Symfony\Component\HttpFoundation\Request;

/**
 * This controller will only allow POST and PUT requests.
 *
 * URI will basically be:
 *   DROP-ZONE-TYPE/DROP-ZONE-ID/ACTION
 *
 * All other data will remain as POST/PUT parameters, in all cases the
 * following parameters are mandatory:
 *   - 'type': draggable item type
 *   - 'id': draggable item identifiers
 * and may contain:
 *   - 'options': arbitrary hashmap of options that the drop zone or drag
 *     item may or may not process, depending upon implementations.
 *   - 'position': for removeAt and dropAt actions
 *   - 'from' and 'to': for the move action
 */
class Controller extends Controller
{
    /**
     * Get handler registry
     *
     * @return HandlerRegistry
     */
    private function getHandlerRegistry()
    {
        return $this->get('ucms.drop.registry');
    }

    /**
     * Get drop handle
     *
     * @param string $type
     *
     * @return DropHandlerInterface
     */
    private function getDropHandler($type)
    {
        return $this->getHandlerRegistry()->getDropHandler($type);
    }

    /**
     * Get dragged item target
     *
     * @param Request $request
     *
     * @return DragItem
     */
    private function getDraggedItem(Request $request)
    {
        return $this
            ->getHandlerRegistry(
                $request->get('type'),
                $request->get('id')
            )
        ;
    }

    /**
     * Validate and return the position parameter from the given request
     *
     * @param Request $request
     * @param string $name
     *
     * @return int
     */
    private function getPosition(Request $request, $name = 'position')
    {
        $position = $request->get('position');

        if (null === $position) {
            throw new \InvalidArgumentException(sprintf("Missing '%s' parameter", $name));
        }
        if (!is_numeric($position)) {
            throw new \InvalidArgumentException(sprintf("Parameter '%s' must be an integer", $name));
        }

        return $position;
    }

    private function createDropResponse($status)
    {
        $ret = [];

        if ($status instanceof DropStatus) {

            $ret['success'] = !$status->isError();
            $ret['message'] = $status->getMessage();

            if ($status->needsRepaint()) {
                if ($status->repaintOnlyItem()) {
                    $ret['render']['target'] = '';
                } else {
                    $ret['render']['drop'] = '';
                }
            }
        } else {
            $ret['success'] = !!$status;
        }
    }

    public function acceptsAction(Request $request, $type, $id)
    {
        $drop     = $this->getDropHandler($type);
        $target   = $this->getDraggedItem($request);
        $options  = $request->get('options', []);

        return $this->createDropResponse($drop->accepts($id, $target, $options));
    }

    public function dropAction(Request $request, $type, $id)
    {
        $drop     = $this->getDropHandler($type);
        $target   = $this->getDraggedItem($request);
        $options  = $request->get('options', []);

        return $this->createDropResponse($drop->drop($id, $target, $options));
    }

    public function removeAction(Request $request, $type, $id)
    {
        $drop     = $this->getDropHandler($type);
        $target   = $this->getDraggedItem($request);
        $options  = $request->get('options', []);

        return $this->createDropResponse($drop->remove($id, $target, $options));
    }

    public function dropAtAction(Request $request, $type, $id)
    {
        $drop     = $this->getHandlerRegistry()->getSortableDropHandler($type);
        $target   = $this->getDraggedItem($request);
        $options  = $request->get('options', []);

        $position = $this->getPosition($request);

        return $this->createDropResponse($drop->dropAt($id, $position, $target, $options));
    }

    public function removeAtAction(Request $request, $type, $id)
    {
        $drop     = $this->getHandlerRegistry()->getSortableDropHandler($type);
        $target   = $this->getDraggedItem($request);
        $options  = $request->get('options', []);

        $position = $this->getPosition($request);

        return $this->createDropResponse($drop->removeAt($id, $position, $target, $options));
    }

    public function move(Request $request, $type, $id)
    {
        $drop     = $this->getHandlerRegistry()->getSortableDropHandler($type);
        $target   = $this->getDraggedItem($request);
        $options  = $request->get('options', []);

        $from     = $this->getPosition($request, 'from');
        $to       = $this->getPosition($request, 'to');

        return $this->createDropResponse($drop->move($id, $from, $to, $target, $options));
    }
}
