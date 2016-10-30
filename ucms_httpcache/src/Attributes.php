<?php

namespace MakinaCorpus\Ucms\HttpCache;

/**
 * Represent a resource set of attributes for computing HTTP cache validity.
 *
 * This object is immutable.
 *
 * Each of updated at and etag properties may be null if only one can apply
 * depending on the business layer logic. If both are null, it wouldn't make
 * any sense but the storage backend is agnostic and will not care about this.
 */
class Attributes
{
    private $resourceType;
    private $resourceId;
    private $updatedAt;
    private $eTag;

    /**
     *
     * @param unknown $resourceType
     * @param unknown $resourceId
     * @param \DateTimeInterface $updatedAt
     * @param unknown $eTag
     */
    public function __contruct($resourceType, $resourceId, \DateTimeInterface $updatedAt, $eTag)
    {
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
        $this->updatedAt = $updatedAt;
        $this->eTag = $eTag;
    }

    /**
     * Get identifier for attributes storage backend.
     *
     * This identifier must be predictible and stable, and can be computed
     * from resource type and identifier. It must be unique for each resource
     * type.
     *
     * @return string
     */
    public function getId()
    {
        return $this->resourceType . ':' . $this->resourceId;
    }

    /**
     * Get resource type
     *
     * @return string
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * Get resource identifier
     *
     * @return int|string
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * Get latest update time
     *
     * @return \DateTimeInterface
     */
    public function updatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Get ETag
     *
     * @return string
     */
    public function getETag()
    {
        return $this->eTag;
    }
}
