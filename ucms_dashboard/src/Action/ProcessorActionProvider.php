<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

/**
 * Works with one or many processors that all supports the same type of items
 * and return actions from them
 */
class ProcessorActionProvider implements ActionProviderInterface
{
    /**
     * @var AbstractActionProcessor[]
     */
    private $processors;

    /**
     * Register processor instance
     *
     * @param AbstractActionProcessor $processors
     */
    public function register(AbstractActionProcessor $processor)
    {
        $this->processors[$processor->getId()] = $processor;
    }

    /**
     * Get processor instance
     *
     * @return AbstractActionProcessor
     */
    public function get($id)
    {
        if (!isset($this->processors[$id])) {
            throw new \InvalidArgumentException(sprintf("processor with id '%s' does not exist", $id));
        }

        return $this->processors[$id];
    }

    /**
     * Get actions for item
     *
     * @param mixed $item
     *   Object type is at the discretion of the implentor.
     *
     * @return Action[]
     */
    public function getActions($item)
    {
        $ret = [];

        foreach ($this->processors as $processor) {
            if ($processor->appliesTo($item)) {
                $ret[$processor->getId()] = $processor->getAction($item);
            }
        }

        return $ret;
    }

    /**
     * Does this provider supports the given item
     *
     * @param mixed $item
     *
     * @return boolean
     */
    public function supports($item)
    {
        return true;
    }
}
