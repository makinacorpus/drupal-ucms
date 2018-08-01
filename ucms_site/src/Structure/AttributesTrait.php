<?php

namespace MakinaCorpus\Ucms\Site\Structure;

trait AttributesTrait
{
    private $attributes;

    public function getAttributes(): array
    {
        // When loading objects from PDO with 'class' fetch mode, the
        // constructor won't be called, hence this lazy init
        if (!is_array($this->attributes)) {
            if (null === $this->attributes) {
                $this->attributes = [];
            } if (is_string($this->attributes)) {
                $this->attributes = unserialize($this->attributes);
            }
        }

        return $this->attributes ?? [];
    }

    public function getAttribute(string $name, $default = null)
    {
        if ($this->hasAttribute($name)) {
            return $this->attributes[$name];
        }

        return $default;
    }

    public function hasAttribute($name): bool
    {
        $this->getAttributes();

        return array_key_exists($name, $this->attributes);
    }

    public function setAttribute(string $name, $value)
    {
        if (null === $value) {
            $this->deleteAttribute($name);
        }

        $this->getAttributes();

        $this->attributes[$name] = $value;
    }

    public function deleteAttribute(string $name)
    {
        if ($this->hasAttribute($name)) {
            unset($this->attributes[$name]);
        }
    }
}
