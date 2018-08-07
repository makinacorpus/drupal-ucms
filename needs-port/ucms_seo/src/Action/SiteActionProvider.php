<?php

namespace MakinaCorpus\Ucms\Seo\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Seo\SeoService;

class SiteActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    /**
     * @var SeoService
     */
    private $service;

    /**
     * @var AccountInterface
     */
    private $currentUser;

    /**
     * Default constructor
     *
     * @param SeoService $service
     * @param AccountInterface $currentUser
     */
    public function __construct(SeoService $service, AccountInterface $currentUser)
    {
        $this->service = $service;
        $this->currentUser = $currentUser;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        if ($this->service->userCanEditSiteSeo($this->currentUser, $item)) {
            $ret[] = new Action($this->t("SEO parameters"), 'admin/dashboard/site/' . $item->id . '/seo-edit', null, 'globe', 1, false, false, false, 'seo');
            $ret[] = new Action($this->t("SEO aliases"), 'admin/dashboard/site/' . $item->id . '/seo-aliases', null, 'link', 2, false, false, false, 'seo');
            $ret[] = new Action($this->t("SEO redirects"), 'admin/dashboard/site/' . $item->id . '/seo-redirects', null, 'random', 3, false, false, false, 'seo');
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof Site;
    }
}
