<?php

namespace MakinaCorpus\Ucms\Site;

/**
 * Facade for using both site storage and site access helpers, that will also
 * carry the site wide configuration; this means to reduce the number of
 * services dependencies for other components
 */
class SiteManager
{
    /**
     * @var SiteAccessService
     */
    protected $access;

    /**
     * @var SiteStorage
     */
    protected $storage;

    /**
     * Default constructor
     *
     * @param SiteStorage $storage
     * @param SiteAccessService $access
     */
    public function __construct(SiteStorage $storage, SiteAccessService $access)
    {
        $this->storage = $storage;
        $this->access = $access;
    }

    /**
     * Get storage service
     *
     * @return SiteStorage
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Get access service
     *
     * @return SiteAccessService
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * Get allowed front-end themes
     *
     * @return string[]
     */
    public function getAllowedThemes()
    {
        return variable_get('ucms_site_allowed_themes');
    }

    /**
     * Get allowed template sites identifiers along with their title
     */
    public function getTemplateList()
    {
        foreach ($this->storage->findTemplates() as $site) {
            yield $site->id => $site->title;
        }
    }

    /**
     * Get allowed front-end themes along with human name
     *
     * @return string[]
     */
    public function getAllowedThemesOptionList()
    {
        $ret = [];
        $all = list_themes();

        foreach ($this->getAllowedThemes() as $theme) {
            if (isset($all[$theme])) {
                $ret[$theme] = $all[$theme]->info['name'];
            } else {
                $ret[$theme] = "oups";
            }
        }

        return $ret;
    }

    /**
     * Set allowed front-end themes
     *
     * @param string[] $themes
     */
    public function setAllowedThemes($themes)
    {
        variable_set('ucms_site_allowed_themes', array_unique($themes));
    }

    /**
     * Is the given theme allowed for front-end
     *
     * @param string $theme
     */
    public function isThemeAllowed($theme)
    {
        return in_array($theme, $this->getAllowedThemes());
    }
}
