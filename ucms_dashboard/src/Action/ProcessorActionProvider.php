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
    private $processors = [];

    /**
     * Register processor instance
     */
    public function register(AbstractActionProcessor $processor)
    {
        $this->processors[$processor->getId()] = $processor;
    }

    /**
     * Get processor instance
     */
    public function get($id): AbstractActionProcessor
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
    public function getActions($item, $primaryOnly = false, array $groups = []): array
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
     */
    public function supports($item): bool
    {
        return true;
    }
}
