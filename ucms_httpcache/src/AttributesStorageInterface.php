<?php

namespace MakinaCorpus\Ucms\HttpCache;

/**
 * Attributes storage backend, which may allow us to store and load the
 * resources HTTP cache attributes without loading the objects to compute
 * them at runtime.
 *
 * Interface is on purpose based upon a key-value store implementation to
 * allow it to be implemented on pretty much anything.
 */
interface AttributesStorageInterface
{
    /**
     * Get attributes for the given resource
     *
     * @param string $resourceType
     * @param int|string $resourceId
     *
     * @return Attributes
     */
    public function get($resourceType, $resourceId);

    /**
     * Delete attributes for the given resource
     *
     * @param string $resourceType
     * @param int|string $resourceId
     */
    public function remove($resourceType, $resourceId);

    /**
     * Insert or update attributes for the given resource
     *
     * @param string $resourceType
     * @param int|string $resourceId
     * @param \DateTimeInterface $updatedAt
     * @param string $eTag
     * @param int $ttl
     */
    public function set($resourceType, $resourceId, \DateTimeInterface $updatedAt, $eTag, $ttl);
}
