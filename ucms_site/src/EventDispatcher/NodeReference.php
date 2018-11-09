<?php

namespace MakinaCorpus\Ucms\Site\EventDispatcher;

/**
 * missing fields/properties:
 *   - source is published in site
 *   - target is referenced in site
 *   - target is published in site
 */
final class NodeReference
{
    const TYPE_FIELD = 'field';
    const TYPE_LINK = 'link';
    const TYPE_MEDIA = 'media';
    const TYPE_UNKNOWN = 'unknown';

    // Those are the {ucms_node_reference} table column names, for PDO.
    private $source_id;
    private $source_title;
    private $ts_source;
    private $source_user_id;
    private $target_id;
    private $target_title;
    private $type;
    private $field_name;
    private $target_exists = true;
    private $ts_touched;

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
            $this->target_exists = $exists;
        }
    }

    public function getSourceId() : int
    {
        return $this->source_id;
    }

    public function getSourceTitle() : string
    {
        return $this->source_title ?? '';
    }

    public function sourceUpdatedAt() : \DateTimeInterface
    {
        if ($this->ts_source) {
            if ($this->ts_source instanceof \DateTimeInterface) {
                return $this->ts_source;
            }
            if (\is_numeric($this->ts_source)) {
                return new \DateTimeImmutable('@'.$this->ts_source);
            }
            if ($value = new \DateTimeImmutable($this->ts_source)) {
                return $value;
            }
        }
        return new \DateTimeImmutable();
    }

    public function getSourceUserId() : int
    {
        return $this->source_user_id ?? 0;
    }

    public function getTargetId() : int
    {
        return $this->target_id;
    }

    public function getTargetTitle() : string
    {
        return $this->target_title ?? '';
    }

    public function getType() : string
    {
        return $this->type ?? self::TYPE_UNKNOWN;
    }

    public function getFieldName() : string
    {
        return $this->field_name ?? '';
    }

    public function touchedAt() : \DateTimeInterface
    {
        if ($this->ts_touched) {
            if ($this->ts_touched instanceof \DateTimeInterface) {
                return $this->ts_touched;
            }
            if (\is_numeric($this->ts_touched)) {
                return new \DateTimeImmutable('@'.$this->ts_touched);
            }
            if ($value = new \DateTimeImmutable($this->ts_touched)) {
                return $value;
            }
        }
        return new \DateTimeImmutable();
    }

    public function targetExists() : bool
    {
        return (bool)$this->target_exists;
    }
}
