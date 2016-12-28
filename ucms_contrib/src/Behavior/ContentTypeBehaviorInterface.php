<?php

namespace MakinaCorpus\Ucms\Contrib\Behavior;

interface ContentTypeBehaviorInterface
{
    /**
     * Provides the identifier of the behavior.
     *
     * @return string
     */
    public function getId();

    /**
     * Provides the name of the behavior.
     *
     * @return string
     */
    public function getName();

    /**
     * Provides the description of the behavior.
     *
     * @return string
     */
    public function getDescription();
}