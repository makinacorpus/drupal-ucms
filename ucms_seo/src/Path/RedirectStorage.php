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
            'path'    => $path,
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

    public function load($conditions)
    {
        $select = $this->db->select('ucms_seo_redirect', 'u');

        foreach ($conditions as $field => $value) {
            if ($field == 'path') {
                // Use LIKE for case-insensitive matching (stupid).
                $select->condition('u.'.$field, $this->db->escapeLike($value), 'LIKE');
            } else {
                $select->condition('u.'.$field, $value);
            }
        }

        return $select
            ->fields('u')
            ->orderBy('u.id', 'DESC')
            ->range(0, 1)
            ->execute()
            ->fetchAssoc('id')
            ;
    }

    public function delete($conditions)
    {
        $path = $this->load($conditions);
        $query = $this->db->delete('ucms_seo_redirect');

        foreach ($conditions as $field => $value) {
            if ($field == 'path') {
                // Use LIKE for case-insensitive matching (still stupid).
                $query->condition($field, $this->db->escapeLike($value), 'LIKE');
            } else {
                $query->condition($field, $value);
            }
        }

        $deleted = $query->execute();
        // @todo Switch to using an event for this instead of a hook.
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
            ->condition('u.path', $this->db->escapeLike($path), 'LIKE')
            ->condition('u.nid', $node_id)
            ->condition('u.site_id', $site_id)
        ;

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

    public function pathHasMatchingRedirect($initial_substring, $site_id = null)
    {
        $query = $this->db->select('ucms_seo_redirect', 'u');

        if (!$site_id) {
            if (!$this->siteManager->hasContext()) {
                return false;
            }
            $site_id = $this->siteManager->getContext()->getId();
        }
        $query->addExpression(1);

        return (bool)$query
            ->condition('u.path', $this->db->escapeLike($initial_substring).'%', 'LIKE')
            ->condition('u.site_id', $site_id)
            ->range(0, 1)
            ->execute()
            ->fetchField()
            ;
    }
}
