<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

/**
 * Actions registry is what fetches actions from any object.
 */
final class ActionRegistry implements ActionProviderInterface, ItemLoaderInterface
{
    private $loaders = [];
    private $providers = [];

    /**
     * Default constructor
     */
    public function __construct(array $providers, array $loaders)
    {
        $this->loaders = $loaders;
        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdFrom($item)
    {
        /** @var \MakinaCorpus\Ucms\Dashboard\Action\ItemLoaderInterface $loader */
        foreach ($this->loaders as $loader) {
            if ($id = $loader->getIdFrom($item)) {
                return $id;
            }
        }

        throw new \InvalidArgumentException("There is no item loader able to get from item");
    }

    /**
     * {@inheritdoc}
     */
    public function load(ItemIdentity $identity)
    {
        /** @var \MakinaCorpus\Ucms\Dashboard\Action\ItemLoaderInterface $loader */
        foreach ($this->loaders as $loader) {
            if ($item = $loader->load($identity)) {
                return $item;
            }
        }

        throw new \InvalidArgumentException(sprintf("There is no item loader able to load '%s' with id '%s'", $identity->type, $identity->id));
    }

    /**
     * Get actions for item
     *
     * @param mixed $item
     *   Item to get actions for
     * @param bool $primaryOnly
     *   If set to true, only primary actions are returned
     * @param string[] $groups
     *   If not empty, only given action groups are returned
     *
     * @return Action[]
     *   Keys are actions identifiers, only the granted actions are returned
     */
    public function getActions($item, bool $primaryOnly = false, array $groups = []): array
    {
        $ret = [];

        /** @var \MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface $provider */
        foreach ($this->providers as $provider) {
            if ($actions = $provider->getActions($item)) {
                /** @var \MakinaCorpus\Ucms\Dashboard\Action\Action $action */
                foreach ($actions as $action) {
                    if ($action->isGranted()) {
                        $ret[$action->getId()] = $action;
                    }
                }
            }
        }

        if ($primaryOnly) {
            $ret = \array_filter($ret, function (Action $action) {
                return $action->isPrimary();
            });
        }

        if ($groups) {
            $ret = \array_filter($ret, function (Action $action) use ($groups) {
                return \in_array($action->getGroup(), $groups);
            });
        }

        \uasort($ret, function (Action $a, Action $b) {
            $ap = $a->getPriority();
            $bp = $b->getPriority();
            if ($ap == $bp) {
                return 0;
            }
            return ($ap < $bp) ? -1 : 1;
        });

        return $ret;
    }
}
