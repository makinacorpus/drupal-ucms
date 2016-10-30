<?php

namespace MakinaCorpus\Ucms\HttpCache;

use Symfony\Component\HttpFoundation\Request;

/**
 * Carries the logic of it all
 */
class CacheResponder
{
    private $storage;

    /**
     * @var CacheStrategyInterface
     */
    private $strategies = [];

    public function __construct(AttributesStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function respond(Request $request)
    {
        foreach ($this->strategies as $strategy) {

            $resource = $strategy->getResourceFromRequest($request);
            if (!$resource) {
                continue;
            }

            $attributes = $this->storage->get($resource->getType(), $resource->getId());
            if (!$attributes) {
                continue;
            }

            // Create response
            break;
        }
    }
}
