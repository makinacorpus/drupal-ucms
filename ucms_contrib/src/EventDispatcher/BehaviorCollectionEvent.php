<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use MakinaCorpus\Ucms\Contrib\Behavior\ContentTypeBehaviorInterface;
use \Symfony\Component\EventDispatcher\Event;

class BehaviorCollectionEvent extends Event
{
    const EVENT_NAME = 'collect';

    /**
     * @var ContentTypeBehaviorInterface[]
     */
    protected $behaviors = [];

    /**
     * Adds a content type behavior.
     *
     * @throws \LogicException
     *   When adding a behavior with the same identifier as another one.
     */
    public function addBehavior(ContentTypeBehaviorInterface $behavior)
    {
        if (isset($this->behaviors[$behavior->getId()])) {
            throw new \LogicException(sprintf(
                "A content type behavior identified by \"%s\" already exists.",
                $behavior->getId()
            ));
        }

        $this->behaviors[$behavior->getId()] = $behavior;
    }

    /**
     * Removes a content type behavior.
     *
     * @param string $identifier
     */
    public function removeBehavior($identifier)
    {
        unset($this->behaviors[$identifier]);
    }

    /**
     * Provides the behavior matching the given identifier.
     *
     * @return ContentTypeBehaviorInterface
     *
     * @throws \DomainException
     *   When the given identifier doesn't match any behavior.
     */
    public function getBehavior($identifier)
    {
        if (!isset($this->behaviors[$identifier])) {
            throw new \DomainException(sprintf(
                "The given identifier (\"%s\") doesn't match any behavior.",
                $identifier
            ));
        }

        return $this->behaviors[$identifier];
    }

    /**
     * Provides all collected behaviors.
     *
     * @return ContentTypeBehaviorInterface[]
     */
    public function getBehaviors()
    {
        return $this->behaviors;
    }

    /**
     * Provides identifiers of all collected behaviors.
     *
     * @return string[]
     */
    public function getBehaviorsIdentifiers()
    {
        return array_keys($this->behaviors);
    }
}
