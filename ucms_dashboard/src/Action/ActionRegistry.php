<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

final class ActionRegistry
{
    /**
     * @var ActionProviderInterface[]
     */
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
     *
     * @return Action[]
     */
    public function getActions($item)
    {
        $ret = [];

        foreach ($this->providers as $provider) {
            if ($provider->supports($item)) {
                $ret = array_merge($ret, $provider->getActions($item));
            }
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
