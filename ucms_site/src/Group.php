<?php

namespace MakinaCorpus\Ucms\Site;

use MakinaCorpus\Ucms\Site\Structure\AttributesTrait;
use MakinaCorpus\Ucms\Site\Structure\DatesTrait;

/**
 * Group data structure
 *
 * Properties are public because of Drupal way of loading objects
 */
class Group
{
    use AttributesTrait;
    use DatesTrait;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var boolean
     */
    private $is_ghost = false;

    /**
     * @var boolean
     */
    private $is_meta = false;

    /**
     * @todo remove this once the storage has a better way to access properties
     *   this being used, theorically, only into storage
     */
    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            throw new \LogicException(sprintf("You may not read the non existing property '%s'", $name));
        }
        return $this->{$name};
    }

    /**
     * Please never use this, only storage may
     */
    public function setId($id)
    {
        if ($this->id) {
            throw new \LogicException("You may not change a group identifier");
        }
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id ?? 0;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function setIsGhost(bool $isGhost)
    {
        $this->is_ghost = $isGhost;
    }

    public function isGhost(): bool
    {
        return (bool)$this->is_ghost;
    }

    public function setIsMeta(bool $isMeta)
    {
        $this->is_meta = $isMeta;
    }

    public function isMeta(): bool
    {
        return (bool)$this->is_meta;
    }
}
