<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteAccessRecord;
use MakinaCorpus\Ucms\Site\SiteManager;

class WebmasterActionProvider extends AbstractActionProvider
{
    private $siteManager;

    private static function buildId(SiteAccessRecord $item): string
    {
        return $item->getSiteId().'-'.$item->getUserId();
    }

    private static function explodeId(string $id): array
    {
        return explode('-', $id, 2);
    }

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getActions($item): array
    {
        $ret = [];

        if ($item instanceof SiteAccessRecord) {
            $site = $this->siteManager->getStorage()->findOne($item->getSiteId());

            $ret[] = $this
                ->create('site_access.change', new TranslatableMarkup("Change role"), 'switch')
                ->primary()
                ->redirectHere()
                ->isGranted(function () use ($site) {
                    return $this->isGranted(Access::OP_SITE_MANAGE_WEBMASTERS, $site);
                })
                ->asLink('ucms_site.admin.site.webmaster_change', ['site' => $item->getSiteId(), 'user' => $item->getUserId()])
            ;

            $ret[] = $this
                ->create('site_access.remove', new TranslatableMarkup("Remove"), 'trash')
                ->primary()
                ->redirectHere()
                ->isGranted(function () use ($site) {
                    return $this->isGranted(Access::OP_SITE_MANAGE_WEBMASTERS, $site);
                })
                ->identity('site_access', $item->generateUniqueId())
                ->asAction(function () use ($item, $site) {
                    $this->siteManager->getAccess()->removeUsers($site, [$item->getUserId()]);
                });
            ;
        }

        return $ret;
    }
}
