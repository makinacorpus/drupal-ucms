<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Implementation that sets up environment information for you, leaving
 * you free of the need to configure a complex service.
 */
abstract class AbstractActionProvider implements ActionProviderInterface
{
    private $authorizationChecker;
    private $container;

    /**
     * {@inheritdoc}
     */
    final public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Set authorization checker
     */
    final public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Get the container
     */
    final protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Is current user granted.
     */
    final protected function isGranted($attributes, $object = null): bool
    {
        return (bool)$this->authorizationChecker->isGranted($attributes, $object);
    }
}
