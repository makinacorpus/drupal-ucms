<?php


namespace MakinaCorpus\Ucms\Layout;

use MakinaCorpus\Ucms\Layout\Context;
use MakinaCorpus\Ucms\Site\SiteManager;


class ContextManager
{
    const NO_CONTEXT          = 0;
    const PAGE_CONTEXT        = 1;
    const TRANSVERSAL_CONTEXT = 2;
    const PARAM_AJAX_TOKEN    = 'token';
    const PARAM_PAGE_TOKEN    = 'edit';
    const PARAM_SITE_TOKEN    = 'site_edit';

    /**
     * @var SiteManager $siteManager
     */
    private $siteManager;

    /**
     * @var Context $pageContext
     */
    private $pageContext;

    /**
     * @var Context $transversalContext
     */
    private $transversalContext;

    /**
     * Constructor
     *
     * @param StorageInterface $storage
     * @param StorageInterface $temporaryStorage
     * @param SiteManager $siteManager
     */
    public function __construct(StorageInterface $storage, StorageInterface $temporaryStorage, SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
        $this->pageContext = new Context($storage, $temporaryStorage);
        $this->transversalContext = new Context($storage, $temporaryStorage);
    }

    /**
     * Provides the page layout context, i.e. the context specific
     * to the current node.
     *
     * @return Context
     */
    public function getPageContext()
    {
        return $this->pageContext;
    }

    /**
     * Provides the transversal layout context, i.e. the context common
     * to the whole site.
     *
     * @return Context
     */
    public function getTransversalContext()
    {
        return $this->transversalContext;
    }

    /**
     * Does the given region belong to the page context?
     *
     * @param string $region Region key
     * @param string $theme Theme key
     *
     * @return boolean
     */
    public function isPageContextRegion($region, $theme)
    {
        $regions = $this->getThemeRegionConfig($theme);
        return (isset($regions[$region]) && ($regions[$region] === self::PAGE_CONTEXT));
    }

    /**
     * Does the given region belong to the transversal context?
     *
     * @param string $region Region key
     * @param string $theme Theme key
     *
     * @return boolean
     */
    public function isTransversalContextRegion($region, $theme)
    {
        $regions = $this->getThemeRegionConfig($theme);
        return (isset($regions[$region]) && ($regions[$region] === self::TRANSVERSAL_CONTEXT));
    }

    /**
     * Is the given region in edit mode?
     *
     * @param string $region Region key
     *
     * @return boolean
     */
    public function isRegionInEditMode($region)
    {
        if ($site = $this->siteManager->getContext()) {
            return
                ($this->getPageContext()->isTemporary() && $this->isPageContextRegion($region, $site->theme)) ||
                ($this->getTransversalContext()->isTemporary() && $this->isTransversalContextRegion($region, $site->theme))
            ;
        }
        return false;
    }

    /**
     * Is there one of the two contexts in edit mode?
     *
     * @return boolean
     */
    public function isInEditMode()
    {
        return ($this->pageContext->isTemporary() || $this->transversalContext->isTemporary());
    }

    /**
     * Get the enabled regions of the given theme.
     *
     * @param string $theme Theme key
     *
     * @return int[]
     */
    public function getThemeRegionConfig($theme)
    {
        $regions = variable_get('ucms_layout_regions_' . $theme);

        if (null === $regions) {
            $regions = array_keys(system_region_list($theme));
            $regions = array_fill_keys($regions, self::PAGE_CONTEXT);
        }

        return array_map('intval', $regions);
    }
}

