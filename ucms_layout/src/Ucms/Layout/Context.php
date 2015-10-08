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
        return $this->hasToken();
    }

    /**
     * Get real life storage
     *
     * @return \Ucms\Layout\StorageInterface
     */
    public function getStorage()
    {
        if (!$this->storage) {
            $this->storage = new DrupalStorage();
        }

        return $this->storage;
    }

    /**
     * Get temporary storage
     *
     * @return \Ucms\Layout\StorageInterface
     */
    public function getTemporaryStorage()
    {
        if (!$this->temporaryStorage) {
            $this->temporaryStorage = (new TemporaryStorage())
                ->setToken(
                    $this->getToken()
                )
            ;
        }

        return $this->temporaryStorage;
    }
}
