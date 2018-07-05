<?php

namespace MakinaCorpus\Ucms\Seo\Path;


use Drupal\Core\Extension\ModuleHandler;
use MakinaCorpus\Ucms\Site\SiteManager;

class RedirectStorage implements RedirectStorageInterface
{
    /**
     * @var \DatabaseConnection
     */
    private $db;
    /**
     * @var \MakinaCorpus\Ucms\Site\SiteManager
     */
    private $siteManager;
    /**
     * @var \Drupal\Core\Extension\ModuleHandler
     */
    private $moduleHandler;

    /**
     * Default constructor.
     *
     * @param \DatabaseConnection $db
     * @param \MakinaCorpus\Ucms\Site\SiteManager $siteManager
     */
    public function __construct(\DatabaseConnection $db, ModuleHandler $moduleHandler, SiteManager $siteManager)
    {
        $this->db = $db;
        $this->siteManager = $siteManager;
        $this->moduleHandler = $moduleHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function save($path, $node_id, $site_id = null, $id = null)
    {
        if (!$site_id) {
            if (!$this->siteManager->hasContext()) {
                return false;
            }
            $site_id = $this->siteManager->getContext()->getId();
        }

        $redirect = [
            'path' => rtrim($path, '/'),
            'nid' => $node_id,
            'site_id' => $site_id,
        ];

        if ($id) {
            $redirect['id'] = $id;

            $this
                ->db
                ->merge('ucms_seo_redirect')
                ->key(['id' => $id])
                ->fields($redirect)
                ->execute()
            ;

            $this->moduleHandler->invokeAll('redirect_insert', [$redirect]);

        } else {

            $this
                ->db
                ->insert('ucms_seo_redirect')
                ->fields($redirect)
                ->execute()
            ;

            $this->moduleHandler->invokeAll('redirect_update', [$redirect]);
        }

        return $redirect;
    }

    /**
     * Specific path processing for database
     */
    private function addPathCondition(\QueryConditionInterface $query, $field, $path)
    {
        // Handle trailing slash, in theory database is not supposed to contain
        // any with a trailing slash, but in doubt, query with both (any other
        // module or user could manually insert data).
        // Paths are supposed to be lower case too.
        $path = drupal_strtolower(rtrim($path, '/'));
        $candidates = [$path, $path.'/'];

        $predicates = db_or();
        foreach ($candidates as $candidate) {
            // Use LIKE for case-insensitive matching in MySQL (stupid).
            $predicates->condition($field, $this->db->escapeLike($candidate), 'LIKE');
        }
        $query->condition($predicates);
    }

    public function load($conditions)
    {
        $select = $this->db->select('ucms_seo_redirect', 'u');

        foreach ($conditions as $field => $value) {
            if ($field == 'path') {
                $this->addPathCondition($select, 'u.'.$field, $value);
            } else {
                $select->condition('u.'.$field, $value);
            }
        }

        return $select
            ->fields('u')
            ->orderBy('u.id', 'DESC')
            ->range(0, 1)
            ->execute()
            ->fetchObject()
            ;
    }

    public function delete($conditions)
    {
        $path = $this->load($conditions);
        $query = $this->db->delete('ucms_seo_redirect');

        foreach ($conditions as $field => $value) {
            if ($field == 'path') {
                $this->addPathCondition($query, 'u.'.$field, $value);
            } else {
                $query->condition($field, $value);
            }
        }

        $deleted = $query->execute();
        $this->moduleHandler->invokeAll('redirect_delete', [$path]);

        return $deleted;

    }

    public function redirectExists($path, $node_id, $site_id = null)
    {
        if (!$site_id) {
            if (!$this->siteManager->hasContext()) {
                return false;
            }
            $site_id = $this->siteManager->getContext()->getId();
        }

        // Use LIKE and NOT LIKE for case-insensitive matching (stupid).
        $query = $this
            ->db
            ->select('ucms_seo_redirect', 'u')
            ->condition('u.nid', $node_id)
            ->condition('u.site_id', $site_id)
        ;
        $this->addPathCondition($query, 'u.path', $path);
        $query->addExpression('1');

        return (bool)$query
            ->range(0, 1)
            ->execute()
            ->fetchField()
            ;
    }

    public function getAliasesForAdminListing($header, $keys = null)
    {
        $query = $this
            ->db
            ->select('ucms_seo_redirect', 'u')
            ->extend('PagerDefault')
            ->extend('TableSort')
        ;

        if ($keys) {
            // Replace wildcards with PDO wildcards.
            $values = '%'.preg_replace('!\*+!', '%', $keys).'%';

            $query->condition('u.path', $values, 'LIKE');
        }

        return $query
            ->fields('u')
            ->orderByHeader($header)
            ->limit(50)
            ->execute()
            ->fetchAll()
            ;
    }

    public function pathHasMatchingRedirect($path, $site_id = null)
    {
        $query = $this->db->select('ucms_seo_redirect', 'u');

        if (!$site_id) {
            if (!$this->siteManager->hasContext()) {
                return false;
            }
            $site_id = $this->siteManager->getContext()->getId();
        }

        $this->addPathCondition($query, 'u.path', $path);
        $query->addExpression(1);

        return (bool)$query
            ->condition('u.site_id', $site_id)
            ->range(0, 1)
            ->execute()
            ->fetchField()
            ;
    }
}
