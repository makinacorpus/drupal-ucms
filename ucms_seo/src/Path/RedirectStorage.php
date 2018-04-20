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
    public function save(string $path, int $nodeId, int $siteId = null, int $id = null)
    {
        if (!$siteId) {
            if (!$this->siteManager->hasContext()) {
                return false;
            }
            $siteId = $this->siteManager->getContext()->getId();
        }

        $redirect = [
            'path'    => $path,
            'nid'     => $nodeId,
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
            ->condition('u.path', $this->database->escapeLike($path), 'LIKE')
            ->condition('u.nid', $nodeId)
            ->condition('u.site_id', $siteId)
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
    public function pathHasMatchingRedirect(string $path, int $siteId = null) : bool
    {
        $query = $this->database->select('ucms_seo_redirect', 'u');

        if (!$siteId) {
            if (!$this->siteManager->hasContext()) {
                return false;
            }
            $siteId = $this->siteManager->getContext()->getId();
        }
        $query->addExpression(1);

        return (bool)$query
            ->condition('u.path', $path)
            ->condition('u.site_id', $siteId)
            ->range(0, 1)
            ->execute()
            ->fetchField()
        ;
    }
}
