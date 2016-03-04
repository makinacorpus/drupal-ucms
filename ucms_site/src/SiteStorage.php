<?php

namespace MakinaCorpus\Ucms\Site;

use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Site storage service
 */
class SiteStorage
{
    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db, EventDispatcherInterface $dispatcher = null)
    {
        $this->db = $db;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Fix site instance
     *
     * @param Site $site
     */
    public function prepareInstance(Site $site)
    {
        $site->state = (int)$site->state;

        if ($site->ts_created) {
            $site->ts_created = \DateTime::createFromFormat('Y-m-d H:i:s', $site->ts_created);
        } else {
            $site->ts_created = new \DateTime();
        }

        if ($site->ts_changed) {
            $site->ts_changed = \DateTime::createFromFormat('Y-m-d H:i:s', $site->ts_changed);
        } else {
            $site->ts_changed = new \DateTime();
        }
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
    public function findByHostname($hostname)
    {
        // Proceed to a few cleanups, in case.
        if (false !== ($pos = strpos($hostname, '://'))) {
          $hostname = substr($hostname, $pos + 3);
        }

        $site = $this
            ->db
            ->query(
                "SELECT * FROM {ucms_site} WHERE http_host = :host LIMIT 1 OFFSET 0",
                [':host' => $hostname]
            )
            ->fetchObject('MakinaCorpus\\Ucms\\Site\\Site')
        ;

        if ($site) {
            $this->prepareInstance($site);
        }

        return $site;
    }

    /**
     * Find template sites
     *
     * @return Site[] $site
     */
    public function findTemplates()
    {
        return $this->loadWithConditions(['is_template' => 1]);
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

        if ($site) {
            $this->prepareInstance($site);
        }

        if (!$site) {
            throw new \InvalidArgumentException("Site does not exists");
        }

        return $site;
    }

    /**
     * Load all sites using conditions
     *
     * @param array $conditions
     *   Keys are field names, values are either single values or list of values
     * @param string $orderField
     *   Field name
     * @param string $order
     *   'asc' or 'desc'
     * @param int $limit
     *
     * @return Site[]
     */
    private function loadWithConditions($conditions = [], $orderField = null, $order = null, $limit = 100)
    {
        $ret = [];

        if (empty($conditions)) {
            return $ret;
        }

        $q = $this
            ->db
            ->select('ucms_site', 's')
            ->addTag('ucms_site_access')
            ->fields('s')
        ;

        foreach ($conditions as $field => $values) {
            // @todo handle date types
            // @todo handle wrong input (non existing fields)
            $q->condition('s.' . $field, $values);
        }

        if ($orderField) {
            $q->orderBy('s.' . $field, $order === 'desc' ? 'desc' : 'asc');
        }

        $sites = $q
            ->execute()
            ->fetchAll(\PDO::FETCH_CLASS, 'MakinaCorpus\\Ucms\\Site\\Site')
        ;

        foreach ($sites as $site) {
            $this->prepareInstance($site);
            $ret[$site->id] = $site;
        }

        return $ret;
    }

    /**
     * Dispatch an event
     *
     * @param Site $site
     * @param string $event
     * @param string $data
     * @param int $userId
     */
    private function dispatch(Site $site, $event, $data = [], $userId = null)
    {
        if (!$this->dispatcher) {
            return;
        }

        $this->dispatcher->dispatch('site:' . $event, new SiteEvent($site, $userId, $data));
    }

    /**
     * Load all sites from the given identifiers
     *
     * @param array $idList
     * @param string $withAccess
     *
     * @return Site[]
     */
    public function loadAll($idList = [], $withAccess = true)
    {
        $ret = [];

        if (empty($idList)) {
            return $ret;
        }

        $q = $this
            ->db
            ->select('ucms_site', 's')
        ;

        if ($withAccess) {
            $q->addTag('ucms_site_access');
        }

        $sites = $q
            ->fields('s')
            ->condition('s.id', $idList)
            ->execute()
            ->fetchAll(\PDO::FETCH_CLASS, 'MakinaCorpus\\Ucms\\Site\\Site')
        ;

        // Ensure order is the same
        // FIXME: find a better way
        $sort = [];
        foreach ($sites as $site) {
            $this->prepareInstance($site);
            $sort[$site->id] = $site;
        }
        foreach ($idList as $id) {
            if (isset($sort[$id])) {
                $ret[$id] = $sort[$id];
            }
        }

        return $ret;
    }

    /**
     * Save given site
     *
     * If the given site has no identifier, its identifier will be set
     *
     * @param Site $site
     * @param array $fields
     *   If set, update only the given fields
     * @param int $userId
     *   Who did this!
     */
    public function save(Site $site, array $fields = null, $userId = null)
    {
        $eligible = [
            'title_admin',
            'title',
            'state',
            'theme',
            'http_host',
            'http_redirects',
            'replacement_of',
            'uid',
            'template_id',
            'is_template',
            'type',
            'home_nid',
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

        $values['ts_changed'] = (new \DateTime())->format('Y-m-d H:i:s');

        if ($site->id) {
            $this->dispatch($site, 'preSave', [], $userId);

            $this
                ->db
                ->merge('ucms_site')
                ->key(['id' => $site->id])
                ->fields($values)
                ->execute()
            ;

            $this->dispatch($site, 'save', [], $userId);
        } else {
            $this->dispatch($site, 'preCreate', [], $userId);

            $values['ts_created'] = $values['ts_changed'];

            $id = $this
                ->db
                ->insert('ucms_site')
                ->fields($values)
                ->execute()
            ;

            $site->id = $id;

            $this->dispatch($site, 'create', [], $userId);
        }
    }

    /**
     * Delete the given sites
     *
     * @param Site $site
     * @param int $userId
     *   Who did this!
     */
    public function delete(Site $site, $userId = null)
    {
        $this->dispatch($site, 'preDelete', [], $userId);

        $this->db->delete('ucms_site')->condition('id', $site->id)->execute();

        $this->dispatch($site, 'delete', [], $userId);
    }
}
