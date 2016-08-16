<?php

namespace MakinaCorpus\Ucms\Site\Structure;

trait AttributesTrait
{
    /**
     * @var string
     */
    protected $attributes;

    public function getAttributes()
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

        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        if ($this->hasAttribute($name)) {
            return $this->attributes[$name];
        }

        return $default;
    }

    public function hasAttribute($name)
    {
        $this->getAttributes();

        return array_key_exists($name, $this->attributes);
    }

    public function setAttribute($name, $value)
    {
        if (null === $value) {
            $this->deleteAttribute($name);
        }

        $this->getAttributes();

        $this->attributes[$name] = $value;
    }

    public function deleteAttribute($name)
    {
        if ($this->hasAttribute($name)) {
            unset($this->attributes[$name]);
        }
    }
}
