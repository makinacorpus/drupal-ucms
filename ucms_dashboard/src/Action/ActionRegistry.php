<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

/**
 * Actions registry is what fetches actions from any object.
 */
final class ActionRegistry implements ActionProviderInterface
{
    private $providers = [];

    /**
     * Register providers
     *
     * @param ActionProviderInterface $provider
     */
    public function register(ActionProviderInterface $provider)
    {
        $this->providers[] = $provider;
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
            $ret = array_filter($ret, function (Action $action) {
                return $action->isPrimary();
            });
        }

        if ($groups) {
            $ret = array_filter($ret, function (Action $action) use ($groups) {
                return in_array($action->getGroup(), $groups);
            });
        }

        usort($ret, function (Action $a, Action $b) {
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
