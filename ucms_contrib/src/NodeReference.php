<?php

namespace MakinaCorpus\Ucms\Contrib;

class NodeReference
{
    // Those are the {ucms_node_reference} table column names, for PDO.
    protected $source_id;
    protected $target_id;
    protected $type;
    protected $field_name;
    protected $target_exists = true;

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

    public function getSourceId()
    {
        return $this->source_id;
    }

    public function getTargetId()
    {
        return $this->target_id;
    }

    public function getType()
    {
        return null === $this->type ? 'unknown' : $this->type;
    }

    public function getFieldName()
    {
        return $this->field_name;
    }

    public function targetExists()
    {
        return $this->target_exists;
    }
}
