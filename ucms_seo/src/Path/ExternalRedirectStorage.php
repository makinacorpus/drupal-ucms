<?php

namespace MakinaCorpus\Ucms\Seo\Path;

class ExternalRedirectStorage
{
    private $database;
    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(\DatabaseConnection $database)
    {
        $this->database = $database;
    }

    /**
     * Find a redirect
     */
    public function find(int $siteId, string $path): string
    {
        return $this->database->query('SELECT external_url FROM {ucms_seo_redirect_external} WHERE site_id = ? AND path = ?', [$siteId, $path])->fetchField() ?? '';
    }
}
