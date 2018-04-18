<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

final class NodeReference
{
    const TYPE_FIELD = 'field';
    const TYPE_LINK = 'link';
    const TYPE_MEDIA = 'media';

    // Those are the {ucms_node_reference} table column names, for PDO.
    private $source_id;
    private $target_id;
    private $type;
    private $field_name;
    private $target_exists = true;

    /**
     * Default constructor
     */
    public function __construct(int $sourceId = null, int $targetId = null, string $type = null, string $fieldName = null, bool $exists = true)
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

    public function getSourceId() : int
    {
        return $this->source_id;
    }

    public function getTargetId() : int
    {
        return $this->target_id;
    }

    public function getType() : string
    {
        return null === $this->type ? 'unknown' : $this->type;
    }

    public function getFieldName() : string
    {
        return $this->field_name;
    }

    public function targetExists() : bool
    {
        return $this->target_exists;
    }
}
