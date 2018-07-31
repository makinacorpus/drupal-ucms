<?php

namespace MakinaCorpus\Ucms\Site;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Ucms\Site\EventDispatcher\AllowListEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\MasterInitEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteInitEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

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
    private $allowedThemes = [];
    private $cdnIsSecure = false;
    private $cdnUrl;
    private $context;
    private $db;
    private $dispatcher;
    private $isMaster = false;
    private $masterIsHttps = false;
    private $masterHostname;
    private $postInitRun = false;
    private $storage;
    private $themeHandler;

    /**
     * Default constructor
     */
    public function __construct(
        SiteStorage $storage,
        SiteAccessService $access,
        Connection $db,
        EventDispatcherInterface $dispatcher,
        ThemeHandlerInterface $themeHandler,
        $masterHostname = null,
        $masterIsHttps = false,
        $allowedThemes = [],
        $cdnUrl = null
    ) {
        $this->storage = $storage;
        $this->access = $access;
        $this->db = $db;
        $this->dispatcher = $dispatcher;
        $this->themeHandler = $themeHandler;
        $this->masterHostname = (string)$masterHostname;
        $this->masterIsHttps = (bool)$masterIsHttps;
        $this->allowedThemes = $allowedThemes;
        $this->cdnUrl = $cdnUrl;
    }

    /**
     * Get master hostname
     */
    public function getMasterHostname(): string
    {
        return $this->masterHostname ?? '';
    }

    /**
     * Is current context master site
     */
    public function isMaster(): bool
    {
        return $this->isMaster;
    }

    /**
     * Is master secure (using https)
     */
    public function isMasterHttps(): bool
    {
        return $this->masterIsHttps;
    }

    /**
     * Get CDN URL, can return an empty string
     */
    public function getCdnUrl(): string
    {
        if (!$this->cdnUrl) {
            return '';
        }

        if (false === \strpos($this->cdnUrl, '://')) {
            return ($this->cdnIsSecure ? 'https://' : 'http://').$this->cdnUrl;
        }

        return $this->cdnUrl;
    }

    /**
     * Set current site context
     *
     * @param Site $site
     *   Site we are initing
     * @param Request $request
     *   Incomming request that setup the site
     * @param bool $disablePostDispatch
     *   If set, no event will be raised, please note this should never ever
     *   be used, except during ucms_site_boot() which will pre-set the site
     *   without knowing if the context is valid or not
     */
    public function setContext(Site $site, Request $request, $disablePostDispatch = false)
    {
        $doDispatch = false;

        if (!$this->context || $this->context->getId() !== $site->getId()) {
            $doDispatch = true;
        }

        $this->isMaster = false;
        $this->cdnIsSecure = $request->isSecure();
        $this->context = $site;

        // Dispatch the context init event
        if ($doDispatch) {

            $this->dispatcher->dispatch(SiteEvents::EVENT_INIT, new SiteInitEvent($this->context, $request));

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
        $previous = $this->context ?? false;

        $this->context = null;

        if ($previous) {
            $this->dispatcher->dispatch(SiteEvents::EVENT_DROP, new SiteEvent($previous));
        }
    }

    public function setContextAsMaster(Request $request)
    {
        $this->dropContext();
        $this->isMaster = true;
        $this->cdnIsSecure = $request->isSecure();

        $this->dispatcher->dispatch(SiteEvents::EVENT_MASTER_INIT, new MasterInitEvent($request));
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
        $event = new AllowListEvent(AllowListEvent::THEMES, $this->getDefaultAllowedThemes());
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
        if ($this->allowedThemes) {
            return $this->allowedThemes;
        }

        return [$this->themeHandler->getDefault()];
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
            $templates[$site->id] = $site->getAdminTitle();
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
