<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

/**
 * Represent a possible action over a certain item, this is just a value
 * object that will be used to build UI links or buttons
 */
final class ActionSeparator extends Action
{
    public function __construct($priority = 0, $primary = true)
    {
        parent::__construct(null, null, [], null, $priority, $primary);
    }
}
