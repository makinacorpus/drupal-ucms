<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

/**
 * Interface ActionProcessorInterface
 * @package MakinaCorpus\Ucms\Dashboard\Action
 */
interface ActionProcessorInterface
{
    /**
     * Return the identifier for this processor.
     *
     * May be be sed for internal URLs.
     *
     * @return string
     */
    public function getId();

    /**
     * Checks if the processor is applicable on an item.
     *
     * @param mixed $item
     * @return bool
     */
    public function appliesTo($item);

    /**
     * Get ready to use action
     *
     * @param mixed $item
     *
     * @return Action
     *   Or null if not appliable
     */
    public function getAction($item);
}
