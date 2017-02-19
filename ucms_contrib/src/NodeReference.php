<?php

namespace MakinaCorpus\Ucms\Contrib;

/**
 * Represents a stored link between two nodes.
 */
class NodeReference
{
    // Those are the {ucms_node_reference} table column names, for PDO.
    private $source_id;
    private $target_id;
    private $type;
    private $field_name;
    private $target_exists = true;

    private $source_bundle;
    private $source_title;
    private $target_title;

    /**
     * Default constructor
     *
     * @param int $sourceId
     * @param int $targetId
     * @param string $type
     * @param string $fieldName
     * @param bool $exists
     */
    public function __construct($sourceId = null, $targetId = null, $type = null, $fieldName = null, $exists = true)
    {
        // All are null because of PDO which in the end does not skip constructor...
        if (null !== $sourceId && null !== $targetId) {
            $this->source_id = $sourceId;
            $this->target_id = $targetId;
            $this->type = $type;
            $this->field_name = $fieldName;
            $this->exists = $exists;
        }
    }

    /**
     * Get source identifier
     *
     * @return int
     */
    public function getSourceId()
    {
        return $this->source_id;
    }

    /**
     * Get target identifier
     *
     * @return int
     */
    public function getTargetId()
    {
        return $this->target_id;
    }

    /**
     * Get reference type
     *
     * @return string
     */
    public function getType()
    {
        return null === $this->type ? 'unknown' : $this->type;
    }

    /**
     * Get field name the reference was found into
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->field_name;
    }

    /**
     * Get source bundle
     *
     * @return null|string
     */
    public function getSourceBundle()
    {
        return $this->source_bundle;
    }

    /**
     * Get source title if set
     *
     * @return null|string
     */
    public function getSourceTitle()
    {
        return $this->source_title;
    }

    /**
     * Get target title if set
     *
     * @return null|string
     */
    public function getTargetTitle()
    {
        return $this->target_title;
    }

    /**
     * Does target exists
     *
     * @return bool
     */
    public function targetExists()
    {
        return $this->target_exists;
    }
}
