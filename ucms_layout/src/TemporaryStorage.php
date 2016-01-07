<?php

namespace MakinaCorpus\Ucms\Layout;

/**
 * Layout storage using Drupal cache system for temporarily being edited
 * instances (user is editing)
 */
class TemporaryStorage
{
    use TokenAwareTrait;

    /**
     * @var int
     */
    private $lifetime;

    /**
     * Default constructor
     */
    public function __construct()
    {
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
}
