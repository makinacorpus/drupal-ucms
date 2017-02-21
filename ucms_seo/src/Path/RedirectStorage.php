<?php

namespace MakinaCorpus\Ucms\Seo\Path;

use Drupal\Core\Extension\ModuleHandler;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * Redirect aliases database default storage.
 */
class RedirectStorage implements RedirectStorageInterface
{
    /**
     * @var \DatabaseConnection
     */
    private $database;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var ModuleHandler
     */
    private $moduleHandler;

    /**
     * Default constructor.
     *
     * @param \DatabaseConnection $database
     * @param ModuleHandler $moduleHandler
     * @param SiteManager $siteManager
     */
    public function __construct(\DatabaseConnection $database, ModuleHandler $moduleHandler, SiteManager $siteManager)
    {
        $this->database = $database;
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
     * {@inheritdoc}
     */
    public function load($conditions)
    {
        $select = $this->database->select('ucms_seo_redirect', 'u');

        foreach ($conditions as $field => $value) {
            if ($field == 'path') {
                // Use LIKE for case-insensitive matching (stupid).
                $select->condition('u.'.$field, $this->database->escapeLike($value), 'LIKE');
            } else {
                $select->condition('u.'.$field, $value);
            }
        }

        return $select
            ->fields('u')
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
                // Use LIKE for case-insensitive matching (still stupid).
                $query->condition($field, $this->database->escapeLike($value), 'LIKE');
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
            ->database
            ->select('ucms_seo_redirect', 'u')
            ->condition('u.path', $this->database->escapeLike($path), 'LIKE')
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

    /**
     * {@inheritdoc}
     */
    public function pathHasMatchingRedirect($path, $site_id = null)
    {
        $query = $this->database->select('ucms_seo_redirect', 'u');

        if (!$site_id) {
            if (!$this->siteManager->hasContext()) {
                return false;
            }
            $site_id = $this->siteManager->getContext()->getId();
        }
        $query->addExpression(1);

        return (bool)$query
            ->condition('u.path', $path)
            ->condition('u.site_id', $site_id)
            ->range(0, 1)
            ->execute()
            ->fetchField()
        ;
    }
}
