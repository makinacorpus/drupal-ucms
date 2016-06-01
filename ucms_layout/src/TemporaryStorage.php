<?php

namespace MakinaCorpus\Ucms\Layout;

/**
 * Layout storage using Drupal cache system for temporarily being edited
 * instances (user is editing)
 */
class TemporaryStorage implements StorageInterface
{
    use TokenAwareTrait;

    /**
     * @var int
     */
    private $lifetime;

    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;

        // FIXME inject me
        // Default to 6 hours, just like form API, even though we know
        // it's one of the stupidest thing ever
        $this->lifetime = variable_get('ucms_layout_temporary_lifetime', 21600);
    }

    /**
     * Build temporary identifier using token and provided identifier
     *
     * @param int $id
     *
     * @return string
     */
    protected function buildId($id)
    {
        return 'temp:' . $this->getToken() . ':' . $id;
    }

    /**
     * Clear current session linked to the given context
     */
    public function clear()
    {
        if (!$this->token) {
            throw new \LogicException("Token is not set");
        }

        cache_clear_all($this->buildId('*'), 'cache_layout', true);
    }

    /**
     * {@inheritdoc}
     */
    public function loadAll($idList)
    {
        throw new \Exception("Temporary backend is not meant to work with multiple instances");
    }

    /**
     * {@inheritdoc}
     */
    public function save(Layout $layout)
    {
        cache_set(
            $this->buildId($layout->getId()),
            $layout,
            'cache_layout',
            time() + $this->lifetime
        );
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        if ($id instanceof Layout) {
            $id = $id->getId();
        }

        cache_clear_all($this->buildId($id), 'cache_layout');
    }

    /**
     * {@inheritdoc}
     */
    public function load($id)
    {
        if ($cached = cache_get($this->buildId($id), 'cache_layout')) {
            if ($cached->data instanceof Layout) {
                return $cached->data;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findForNodeOnSite($nodeId, $siteId, $createOnMiss = false)
    {
        $id = (int)$this
            ->db
            ->query(
                "SELECT id FROM {ucms_layout} WHERE nid = ? AND site_id = ?",
                [$nodeId, $siteId]
            )
            ->fetchField()
        ;

        if (!$id) {
            throw new \LogicException("Temporary storage points to a non existing layout");
        }

        return $this->load($id);
    }

    /**
     * {@inheritdoc}
     */
    public function resetCacheForNode($nodeId)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function resetCacheForSite($siteId)
    {
    }
}
