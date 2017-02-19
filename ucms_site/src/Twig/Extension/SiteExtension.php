<?php

namespace MakinaCorpus\Ucms\Site\Twig\Extension;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

/**
 * Displays any object's actions
 */
class SiteExtension extends \Twig_Extension
{
    use StringTranslationTrait;

    private $siteManager;

    /**
     * Default constructor
     *
     * @param ActionRegistry $actionRegistry
     */
    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('ucms_site_state', [$this, 'renderState'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('ucms_site_url', [$this, 'renderSiteUrl']),
        ];
    }

    /**
     * Render state
     *
     * @param int $state
     *
     * @return string
     */
    public function renderState($state)
    {
        $list = SiteState::getList();

        if (isset($list[$state])) {
            return $this->t($list[$state]);
        }

        return $this->t("Unknown");
    }

    /**
     * Render site link
     *
     * @param int|Site $site
     *   Site identifier, if site is null
     * @param string $path
     *   Drupal path to hit in site
     * @param mixed[] $options
     *   Link options, see url()
     *
     * @return string
     */
    public function renderSiteUrl($site, $path = null, array $options = [], $ignoreSso = false, $dropDestination = true)
    {
        return $this->siteManager->getUrlGenerator()->generateUrl($site, $path, $options, $ignoreSso, $dropDestination);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ucms_site';
    }
}
