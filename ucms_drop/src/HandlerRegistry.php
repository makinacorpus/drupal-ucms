<?php

namespace MakinaCorpus\Ucms\Drop;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * This registry allows the user to register handlers for each droppable type
 * and fetch them; it also act as a draggable item factory.
 */
class HandlerRegistry implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $dropHandlers = [];
    private $dropHandlerInstances = [];

    /**
     * Register droppable
     *
     * @param unknown $droppable
     */
    public function registerInstance(DropHandlerInterface $droppable)
    {
        $this->dropHandlerInstances[$droppable->getType()] = $droppable;
    }

    /**
     * Register droppable as a service
     *
     * @param string $id
     */
    public function registerService($type, $id)
    {
        $this->dropHandlers[$type] = $id;
    }

    /**
     * @return DropHandlerInterface
     */
    public function getDropHandler($type)
    {
        if (isset($this->dropHandlerInstances[$type])) {
            return $this->dropHandlerInstances[$type];
        }

        if (isset($this->dropHandlers[$type])) {
            $instance = $this->container->get(($this->dropHandlers[$type]));

            if (!$instance instanceof DropHandlerInterface) {
                throw new \InvalidArgumentException(sprintf(
                    "'%s' service is not a \MakinaCorpus\Ucms\Drop\DropHandlerInterface instance",
                    $this->dropHandlers[$type]
                ));
            }
            if ($instance->getType() !== $type) {
                throw new \InvalidArgumentException(sprintf(
                    "'%s' service type mismatch, expected '%s' and got '%s'",
                    $this->dropHandlers[$type],
                    $type,
                    $instance->getType()
                ));
            }

            return $this->dropHandlerInstances[$type] = $instance;
        }

        throw new \InvalidArgumentException(sprintf("Unknown '%s' drop handler"));
    }

    /**
     * Alias of ::getDropHandler() that will check for sortable type
     *
     * @param string $type
     *
     * @return SortableDropHandlerInterface
     */
    public function getSortableDropHandler($type)
    {
        $handler = $this->getDropHandler($type);

        if (!$handler instanceof SortableDropHandlerInterface) {
            throw new \InvalidArgumentException(sprintf("'%s' drop handler is not sortable", $type));
        }

        return $handler;
    }

    /**
     * Create draggable instance
     *
     * @param string $type
     * @param scalar $id
     *
     * @return \MakinaCorpus\Ucms\Drop\DragItem
     */
    public function createDraggable($type, $id)
    {
        if (empty($type)) {
            throw new \InvalidArgumentException("type is empty");
        }
        if (empty($id)) {
            throw new \InvalidArgumentException("id is empty");
        }
        if (!is_string($type)) {
            throw new \InvalidArgumentException("type must be a string");
        }
        if (!is_scalar($id)) {
            throw new \InvalidArgumentException("id must be an integer or a string");
        }

        return new DragItem($type, $id);
    }
}
