<?php

namespace MakinaCorpus\Ucms\Site;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
    private $allowedTypes = [];
    private $cdnIsSecure = false;
    private $cdnUrl;
    private $context;
    private $db;
    private $dispatcher;
    private $isMaster = false;
    private $masterIsHttps = false;
    private $masterHostname;
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
        $allowedTypes = [],
        $cdnUrl = null
    ) {
        $this->access = $access;
        $this->allowedThemes = $allowedThemes;
        $this->allowedTypes = $allowedTypes;
        $this->cdnUrl = $cdnUrl;
        $this->db = $db;
        $this->dispatcher = $dispatcher;
        $this->masterHostname = (string)$masterHostname;
        $this->masterIsHttps = (bool)$masterIsHttps;
        $this->storage = $storage;
        $this->themeHandler = $themeHandler;
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
     */
    public function getAllowedTypes(): array
    {
        return $this->allowedTypes ? $this->allowedTypes : ['site' => new TranslatableMarkup("Site")];
    }

    /**
     * Get type human readable name
     */
    public function getTypeName(string $type): string
    {
        return $this->getAllowedTypes()[$type] ?? new TranslatableMarkup("None");
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

        foreach ($this->getAllowedThemes() as $theme) {
            if ($label = $this->themeHandler->getName($theme)) {
                $ret[$theme] = $label;
            } else {
                $ret[$theme] = new TranslatableMarkup("Non existing theme @theme", ['@theme' => $theme]);
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

        foreach ($this->getDefaultAllowedThemes() as $theme) {
            if ($label = $this->themeHandler->getName($theme)) {
                $ret[$theme] = $label;
            } else {
                $ret[$theme] = new TranslatableMarkup("Non existing theme @theme", ['@theme' => $theme]);
            }
        }

        return $ret;
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
