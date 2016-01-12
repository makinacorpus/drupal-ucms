<?php

namespace MakinaCorpus\Ucms\Site;

/**
 * Site finder service
 */
class SiteFinder
{
    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var Site
     */
    private $context;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * Set current site context
     *
     * @param Site $site
     */
    public function setContext(Site $site)
    {
        $this->context = $site;
    }

    /**
     * Get current context
     *
     * @return Site
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Remove current context
     */
    public function dropContext()
    {
        $this->context = null;
    }

    /**
     * Find by hostname
     *
     * @param string $hostname
     * @param boolean $setAsContext
     *
     * @return Site
     *   Site instance, or null if not found
     */
    public function findByHostname($hostname, $setAsContext = false)
    {
        $site = $this
            ->db
            ->query(
                "SELECT * FROM {ucms_site} WHERE http_host = :host LIMIT 1 OFFSET 0",
                [':host' => $hostname]
            )
            ->fetchObject('MakinaCorpus\\Ucms\\Site\\Site')
        ;

        if ($setAsContext) {
            if ($site) {
                $this->setContext($site);
            } else {
                $this->dropContext();
            }
        }

        return $site;
    }

    /**
     * Load site by identifier
     *
     * @param int $id
     *
     * @return Site
     *
     * @throws \InvalidArgumentException
     */
    public function findOne($id)
    {
        $site = $this
            ->db
            ->query(
                "SELECT * FROM {ucms_site} WHERE id = :id LIMIT 1 OFFSET 0",
                [':id' => $id]
            )
            ->fetchObject('MakinaCorpus\\Ucms\\Site\\Site')
        ;

        if (!$site) {
            throw new \InvalidArgumentException("Site does not exists");
        }

        return $site;
    }

    /**
     * Save given site
     *
     * If the given site has no identifier, its identifier will be set
     *
     * @param Site $site
     * @param array $fields
     *   If set, update only the given fields
     */
    public function save(Site $site, array $fields = null)
    {
        $eligible = [
            'title_admin',
            'title',
            'state',
            'theme',
            'http_host',
            'relacement_of',
            'uid',
        ];

        if (null === $fields) {
            $fields = $eligible;
        } else {
            $fields = array_intersect($eligible, $fields);
        }

        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $site->{$field};
        }

        if ($site->id) {
            $this
                ->db
                ->merge('ucms_site')
                ->key(['id' => $site->id])
                ->fields($values)
                ->execute()
            ;
        } else {

            $id = $this
                ->db
                ->insert('ucms_site')
                ->fields($values)
                ->execute()
            ;

            $site->id = $id;
        }
    }
}
