<?php

namespace MakinaCorpus\Ucms\Site;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MakinaCorpus\Ucms\Site\EventDispatcher\AllowListEvent;

/**
 * Facade for using both site storage and site access helpers, that will also
 * carry the site wide configuration; this means to reduce the number of
 * services dependencies for other components
 *
 * This class carries the current site context, as an instance of Site you can
 * fetch using the getContext() method, but implementors might also add as many
 * dependent contextes as they need.
 *
 * A dependent context is any kind of object or value, identifier by a string
 * key, unknown to this API but that any module extending might set. In order
 * to set correctly dependent contextes, an event is dispatched whenever the
 * site context is changed.
 */
class SiteManager
{
    private $access;
    private $storage;
    private $context;
    private $dependentContext = [];
    private $db;
    private $dispatcher;
    private $postInitRun = false;

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
     * @param bool $disablePostDispatch
     *   If set, no event will be raised, please note this should never ever
     *   be used, except during ucms_site_boot() which will pre-set the site
     *   without knowing if the context is valid or not
     */
    public function setContext(Site $site, $disablePostDispatch = false)
    {
        $doDispatch = false;

        if (!$this->context || $this->context->getId() !== $site->getId()) {
            $doDispatch = true;
        }

        $this->context = $site;

        // Dispatch the context init event
        if ($doDispatch) {

            // On context change, we need to remove the older contextes else we
            // would experience strict fails on dependent context set
            $this->dependentContext = [];

            $this->dispatcher->dispatch(SiteEvents::EVENT_INIT, new SiteEvent($this->context));

            if ($disablePostDispatch) {
                // We are in hook_boot(), set post-init to run later during
                // hook_init() ensuring that Drupal is fully bootstrapped,
                // it fixes lots of bugs such has the layout manager may use
                // the Drupal menu to find the current node context
                $this->postInitRun = false;
            } else {
                $this->dispatchPostInit();
            }
        }
    }

    /**
     * This is public because it must be run manually from Drupal code, but
     * please never, ever, run this manually or I'll do kill you. Slowly.
     */
    public function dispatchPostInit()
    {
        if (!$this->postInitRun && $this->context) {
            $this->postInitRun = true;
            $this->dispatcher->dispatch(SiteEvents::EVENT_POST_INIT, new SiteEvent($this->context));
        }
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
        $oldContext = false;

        if ($this->context) {
            $oldContext = $this->context;
        }

        $this->context = null;
        $this->dependentContext = [];

        if ($oldContext) {
            $this->dispatcher->dispatch(SiteEvents::EVENT_DROP, new SiteEvent($oldContext));
        }
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
     * Has dependent context
     *
     * @param string $name
     */
    public function hasDependentContext($name)
    {
        return isset($this->dependentContext[$name]);
    }

    /**
     * Get dependent context
     *
     * @param string $name
     *
     * @param mixed
     */
    public function getDependentContext($name)
    {
        if (!isset($this->dependentContext[$name])) {
            throw new \InvalidArgumentException(sprintf("there is no dependent context '%s'", $name));
        }

        return $this->dependentContext[$name];
    }

    /**
     * Set dependent context
     *
     * @param string $name
     * @param mixed $value
     * @param bool $allowOverride
     */
    public function setDependentContext($name, $value, $allowOverride = false)
    {
        if (!$allowOverride && isset($this->dependentContext[$name])) {
            throw new \LogicException(sprintf("you are overriding an existing dependent context '%s', are you sure you meant to do this?", $name));
        }

        $this->dependentContext[$name] = $value;
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
        $event = new AllowListEvent(AllowListEvent::THEMES, variable_get('ucms_site_allowed_themes', []));
        $this->dispatcher->dispatch(AllowListEvent::EVENT_THEMES, $event);

        return $event->getAllowedItems();
    }

    /**
     * Get allowed front-end themes
     *
     * @return string[]
     */
    public function getDefaultAllowedThemes()
    {
        return variable_get('ucms_site_allowed_themes', []);
    }

    /**
     * Get allowed site types
     *
     * @return string[]
     */
    public function getAllowedTypes()
    {
        return variable_get('ucms_site_allowed_types', [
            'default' => t("Default"), // @todo
        ]);
    }

    /**
     * Get URL in site
     *
     * @param int|Site $site
     *   Site identifier, if site is null
     * @param string $path
     *   Drupal path to hit in site
     * @param mixed[] $options
     *   Link options, see url()
     *
     * @return mixed
     *   First value is the string path
     *   Second value are updates $options
     */
    public function getUrlInSite($site, $path, $options = [])
    {
        if ($site instanceof Site) {
            $site = $site->getId();
        }

        if ($this->hasContext() && $this->getContext()->getId() == $site) {
            return [$path, $options];
        }

        $realpath = 'sso/goto/' . $site;

        if (isset($_GET['destination'])) {
            $options['query']['form_redirect'] = $_GET['destination'];
            unset($_GET['destination']);
        } else if (isset($options['query']['destination'])) {
            $options['query']['form_redirect'] = $options['query']['destination'];
        }

        $options['query']['destination'] = $path;

        return [$realpath, $options];
    }

    /**
     * Get type human readable name
     *
     * @param string $type
     *
     * @return string
     */
    public function getTypeName($type)
    {
        $allowedTypes = $this->getAllowedTypes();

        if ($type && isset($allowedTypes[$type])) {
            return $allowedTypes[$type];
        }

        return t("None"); // @todo
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
     * Get allowed front-end themes along with human names
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
     * Get allowed front-end themes along with human names
     *
     * @return string[]
     */
    public function getDefaultAllowedThemesOptionList()
    {
        $ret = [];
        $all = list_themes();

        foreach ($this->getDefaultAllowedThemes() as $theme) {
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
