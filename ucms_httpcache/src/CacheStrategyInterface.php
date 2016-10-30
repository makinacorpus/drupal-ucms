<?php

namespace MakinaCorpus\Ucms\HttpCache;

use Symfony\Component\HttpFoundation\Request;

/**
 * Business logic should be implemented into this object in order for the
 * early startup shortcuts to work
 */
interface CacheStrategyInterface
{
    /**
     * Get resource from request
     *
     * This may be run onto every incoming request, if the request cannot be
     * understood by the implementation, just return null
     *
     * @return null|Resource
     */
    public function getResourceFromRequest(Request $request);
}
