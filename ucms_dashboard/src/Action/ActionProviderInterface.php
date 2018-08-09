<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

interface ActionProviderInterface
{
    /**
     * Get actions for item
     *
     * @param mixed $item
     *   Object type is at the discretion of the implentor.
     *
     * @return Action[]
     */
    public function getActions($item): array;
}
