<?php

namespace Ucms\Layout;

class Context
{
    use TokenAwareTrait;

    /**
     * @var \Ucms\Layout\StorageInterface
     */
    private $storage;

    /**
     * @var \Ucms\Layout\TemporaryStorage
     */
    private $temporaryStorage;

    /**
     * @var \Ucms\Layout\Layout
     */
    private $layout;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->storage = new DrupalStorage();
    }

    /**
     * Commit session changes and restore storage
     *
     * @return \Ucms\Layout\Context
     */
    public function commit()
    {
        // Throws a nice exception if no token
        $this->getToken();

        if (!$this->layout instanceof Layout) {
            throw new \LogicException("No contextual instance is set, cannot commit");
        }

        // This gets the temporary storage until now
        $this->getStorage()->delete($this->layout->getId());
        // Loaded instance is supposed to be the false one
        $this->storage->save($this->layout);

        $this->setToken(null);

        return $this;
    }

    /**
     * Rollback session changes and restore storage
     *
     * @return \Ucms\Layout\Context
     */
    public function rollback()
    {
        // Throws a nice exception if no token
        $this->getToken();

        if (!$this->layout instanceof Layout) {
            throw new \LogicException("No contextual instance is set, cannot commit");
        }

        // Loaded instance is supposed to be the false one
        // This gets the temporary storage until now
        $this->getStorage()->delete($this->layout->getId());

        $this->setToken(null);

        // Reload the real layout
        $this->layout = $this->storage->load($this->layout->getId());

        return $this;
    }

    /**
     * Set current layout
     *
     * @param Layout $layout
     *
     * @return \Ucms\Layout\Context
     */
    public function setCurrentLayout(Layout $layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * Get current layout
     */
    public function getCurrentLayout()
    {
        return $this->layout;
    }

    /**
     * Does this context has a temporary token
     *
     * @return boolean
     */
    public function isTemporary()
    {
        return $this->hasToken() && $this->layout instanceof Layout;
    }

    /**
     * Get real life storage
     *
     * @return \Ucms\Layout\StorageInterface
     */
    public function getStorage()
    {
        if ($this->hasToken()) {
            if (!$this->temporaryStorage) {
                $this->temporaryStorage = new TemporaryStorage($this->token);
                $this->temporaryStorage->setToken($this->getToken());
            }

            return $this->temporaryStorage;
        }

        return $this->storage;
    }
}
