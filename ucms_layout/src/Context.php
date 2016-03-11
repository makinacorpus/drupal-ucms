<?php

namespace MakinaCorpus\Ucms\Layout;

class Context
{
    use TokenAwareTrait;

    /**
     * Layout context is initializing
     */
    const EVENT_INIT = 'ucms_layout.context_init';

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var TemporaryStorage
     */
    private $temporaryStorage;

    /**
     * @var Layout
     */
    private $layout;

    /**
     * @var int
     */
    private $layoutNodeId;

    /**
     * @var int
     */
    private $layoutSiteId;

    /**
     * Default constructor
     *
     * @param StorageInterface $storage
     * @param StorageInterface $temporaryStorage
     */
    public function __construct(StorageInterface $storage, StorageInterface $temporaryStorage)
    {
        $this->storage = $storage;
        $this->temporaryStorage = $temporaryStorage;
    }

    /**
     * Commit session changes and restore storage
     *
     * @return Context
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
     * @return Context
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
     * @return Context
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
        if (!$this->layout && $this->layoutNodeId) {
            $this->layout = $this->getStorage()->findForNodeOnSite($this->layoutNodeId, $this->layoutSiteId, true);
        }

        return $this->layout;
    }

    /**
     * Set current layout node ID
     *
     * @param int $nid
     * @param int $siteId
     */
    public function setCurrentLayoutNodeId($nid, $siteId)
    {
        if ($this->layoutNodeId) {
            throw new \LogicException("You can't change the current layout node ID.");
        }

        $this->layoutNodeId = $nid;
        $this->layoutSiteId = $siteId;
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
     * @return StorageInterface
     */
    public function getStorage()
    {
        if ($this->hasToken()) {
            return $this->temporaryStorage->setToken($this->getToken());
        }

        return $this->storage;
    }
}
