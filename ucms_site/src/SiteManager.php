<?php

namespace MakinaCorpus\Ucms\Site;

use Drupal\Core\Session\AccountInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;

/**
 * Facade for using both site storage and site access helpers, that will also
 * carry the site wide configuration; this means to reduce the number of
 * services dependencies for other components
 */
final class SiteManager
{
    /**
     * @var SiteAccessService
     */
    private $access;

    /**
     * @var SiteStorage
     */
    private $storage;

    /**
     * @var Site
     */
    private $context;

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
     * @param SiteStorage $storage
     * @param SiteAccessService $access
     * @param \DatabaseConnection $db
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        SiteStorage $storage,
        SiteAccessService $access,
        \DatabaseConnection $db,
        EventDispatcherInterface $dispatcher
    ) {
        $this->storage = $storage;
        $this->access = $access;
        $this->db = $db;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Set current site context
     *
     * @param Site $site
     */
    public function setContext(Site $site)
    {
        $this->context = $site;
        $this->dispatcher->dispatch('site:init', new SiteEvent($site));
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
     * Has context
     *
     * @return boolean
     */
    public function hasContext()
    {
        return !!$this->context;
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
        return variable_get('ucms_site_allowed_themes', []);
    }

    /**
     * Get allowed template sites identifiers along with their title
     */
    public function getTemplateList()
    {
        $templates = [];
        foreach ($this->storage->findTemplates() as $site) {
            $templates[$site->id] = $site->title;
        }

        return $templates;
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

    /**
     * Get site home node type
     *
     * @return string
     *   It may be null, beware
     */
    public function getHomeNodeType()
    {
        return variable_get('ucms_site_home_node_type');
    }

    /**
     * Set home node type
     *
     * @param string $type
     */
    public function setHomeNodeType($type)
    {
        variable_set('ucms_site_home_node_type', $type);
    }

    /**
     * Create site menu
     *
     * @param Site $site
     * @param string $name
     *   Drupal menu_name suffix (will be prefixed by something technical)
     */
    public function createMenu(Site $site, $name)
    {
        throw new \Exception("Not implemented yet");
    }

    /**
     * Get site menus
     *
     * @param Site $site
     */
    public function getMenus(Site $site)
    {
        return $this->db->query('SELECT * FROM {menu_custom} WHERE site_id = ?', [$site->id])->fetchAllAssoc('menu_name', \PDO::FETCH_ASSOC);
    }

    /**
     * Load sites for which the user is webmaster
     *
     * @param AccountInterface $account
     *
     * @return Site[]
     */
    public function loadWebmasterSites(AccountInterface $account)
    {
        $roles = $this->getAccess()->getUserRoles($account);

        foreach ($roles as $grant) {
            if ($grant->getRole() !== Access::ROLE_WEBMASTER) {
                unset($roles[$grant->getSiteId()]);
            }
        }

        return $this->getStorage()->loadAll(array_keys($roles));
    }

    /**
     * Load sites for which the user is a part of
     *
     * @param AccountInterface $account
     *
     * @return Site[]
     */
    public function loadOwnSites(AccountInterface $account)
    {
        $roles = $this->getAccess()->getUserRoles($account);

        return $this->getStorage()->loadAll(array_keys($roles));
    }
}
