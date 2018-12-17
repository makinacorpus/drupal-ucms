<?php

namespace MakinaCorpus\Ucms\Seo\Path;

use Drupal\Core\Extension\ModuleHandler;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * Redirect aliases database default storage.
 */
class RedirectStorage implements RedirectStorageInterface
{
    private $database;
    private $moduleHandler;
    private $siteManager;

    /**
     * Default constructor.
     */
    public function __construct(\DatabaseConnection $database, ModuleHandler $moduleHandler, SiteManager $siteManager)
    {
        $this->database = $database;
        $this->moduleHandler = $moduleHandler;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritDoc}
     */
    public function save(string $path, int $nodeId, int $siteId = null, int $id = null)
    {
        if (!$siteId) {
            if (!$this->siteManager->hasContext()) {
                return false;
            }
            $siteId = $this->siteManager->getContext()->getId();
        }

        $redirect = [
            'path' => \rtrim($path, '/'),
            'nid' => $nodeId,
            'site_id' => $siteId,
        ];

        if ($id) {
            $redirect['id'] = $id;

            $this
                ->database
                ->merge('ucms_seo_redirect')
                ->key(['id' => $id])
                ->fields($redirect)
                ->execute()
            ;

            $this->moduleHandler->invokeAll('redirect_insert', [$redirect]);

        } else {

            $this
                ->database
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
        $path = \drupal_strtolower(\rtrim($path, '/'));
        $candidates = [$path, $path.'/'];

        $predicates = \db_or();
        foreach ($candidates as $candidate) {
            // Use LIKE for case-insensitive matching in MySQL (stupid).
            $predicates->condition($field, $this->database->escapeLike($candidate), 'LIKE');
        }
        $query->condition($predicates);
    }

    /**
     * {@inheritdoc}
     */
    public function createSelectQuery(): \SelectQuery
    {
        $select = $this->database->select('ucms_seo_redirect', 'u');
        $select->fields('u');

        $select->leftJoin('node', 'n', "n.nid = u.nid");
        $select->addField('n', 'nid', 'node_exists');
        $select->addField('n', 'title', 'node_title');
        $select->addField('n', 'type', 'node_type');

        $select->leftJoin('ucms_site', 's', "s.id = u.site_id");
        $select->addField('s', 'title_admin', 'site_title');

        return $select;
    }

    /**
     * {@inheritdoc}
     */
    public function load($conditions)
    {
        $select = $this->createSelectQuery();

        foreach ($conditions as $field => $value) {
            if ($field == 'path') {
                $this->addPathCondition($select, 'u.'.$field, $value);
            } else {
                $select->condition('u.'.$field, $value);
            }
        }

        return $select
            ->orderBy('u.id', 'DESC')
            ->range(0, 1)
            ->execute()
            ->fetchObject(Redirect::class)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($conditions)
    {
        $path = $this->load($conditions);
        $query = $this->database->delete('ucms_seo_redirect');

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

    /**
     * {@inheritdoc}
     */
    public function redirectExists(string $path, int $nodeId, int $siteId = null) : bool
    {
        if (!$siteId) {
            if (!$this->siteManager->hasContext()) {
                return false;
            }
            $siteId = $this->siteManager->getContext()->getId();
        }

        // Use LIKE and NOT LIKE for case-insensitive matching (stupid).
        $query = $this
            ->database
            ->select('ucms_seo_redirect', 'u')
            ->condition('u.nid', $nodeId)
            ->condition('u.site_id', $siteId)
        ;
        $this->addPathCondition($query, 'u.path', $path);
        $query->addExpression('1');

        return (bool)$query
            ->range(0, 1)
            ->execute()
            ->fetchField()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function pathHasMatchingRedirect(string $path, int $siteId = null) : bool
    {
        $query = $this->database->select('ucms_seo_redirect', 'u');

        if (!$siteId) {
            if (!$this->siteManager->hasContext()) {
                return false;
            }
            $siteId = $this->siteManager->getContext()->getId();
        }

        $this->addPathCondition($query, 'u.path', $path);
        $query->addExpression(1);

        return (bool)$query
            ->condition('u.site_id', $siteId)
            ->range(0, 1)
            ->execute()
            ->fetchField()
        ;
    }
}
