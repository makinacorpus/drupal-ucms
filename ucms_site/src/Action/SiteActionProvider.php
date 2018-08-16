<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

class SiteActionProvider extends AbstractActionProvider
{
    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item): array
    {
        $ret = [];

        if ($item instanceof Site) {
            $siteId = $item->getId();

            $ret[] = $this
                ->create('site.view', new TranslatableMarkup("Informations"), 'eye-open', -10)
                ->primary()
                ->isGranted(function () use ($item) {
                    return $this->isGranted(Access::OP_SITE_VIEW_IN_ADMIN, $item);
                })
                ->asLink('ucms_site.admin.site.view', ['site' => $siteId])
            ;

            $ret[] = $this
                ->create('site.goto', new TranslatableMarkup("Go to site"), 'share-alt', -5)
                ->primary()
                ->isGranted(function () use ($item) {
                    return $this->isGranted(Access::OP_VIEW, $item);
                })
                ->asLink('ucms_site.go_to_site', ['siteId' => $siteId])
            ;

            $ret[] = $this
                ->create('site.update', new TranslatableMarkup("Edit"), 'pencil', 0)
                ->redirectHere()
                ->group('update')
                ->isGranted(function () use ($item) {
                    return $this->isGranted(Access::OP_UPDATE, $item);
                })
                ->asLink('ucms_site.admin.site.edit', ['site' => $siteId])
            ;

            $ret[] = $this
                ->create('site.hostname', new TranslatableMarkup("Change hostname"), 'pencil', -2)
                ->redirectHere()
                ->group('update')
                ->isGranted(function () use ($item) {
                    return $this->isGranted(Access::OP_SITE_CHANGE_HOSTNAME, $item);
                })
                ->asLink('ucms_site.admin.site.change_hostname', ['site' => $siteId])
            ;

            // Append all possible state switch operations
            /*
            $i = 10;
            foreach ($access->getAllowedTransitions($account, $item) as $state => $name) {
                $ret[] = $this
                    ->create('site.hostname', $this->t("Change hostname"), 'pencil', -2)
                    ->redirectHere()
                    ->isGranted(function () use ($item) {
                        return $this->isGranted(Access::OP_SITE_CHANGE_HOSTNAME, $item);
                    })
                    ->asLink('ucms_site.admin.site.change_hostname', ['site' => $siteId])
                ;
                $ret[] = new Action($this->t("Switch to @state", ['@state' => $this->t($name)]), 'ucms_site.admin.site.switch', ['site' => $siteId, 'state' => $state], 'refresh', ++$i, false, true, false, 'switch');
            }
             */

            $ret[] = $this
                ->create('site.useradd', new TranslatableMarkup("Add existing user"), 'user', 100)
                ->redirectHere()
                ->group('user')
                ->isGranted(function () use ($item) {
                    return $this->isGranted(Access::OP_SITE_MANAGE_WEBMASTERS, $item);
                })
                ->asLink('ucms_site.admin.site.webmaster_add', ['site' => $siteId])
            ;

            $ret[] = $this
                ->create('site.users', new TranslatableMarkup("Manage users"), 'user', 102)
                ->redirectHere()
                ->group('user')
                ->isGranted(function () use ($item) {
                    return $this->isGranted(Access::OP_SITE_MANAGE_WEBMASTERS, $item);
                })
                ->asLink('ucms_site.admin.site.webmaster', ['site' => $siteId])
            ;

            $ret[] = $this
                ->create('site.delete', new TranslatableMarkup("Delete"), 'trash', 1000)
                ->redirectHere()
                ->group('switch')
                ->isGranted(function () use ($item) {
                    return $this->isGranted(Access::OP_DELETE, $item);
                })
                // @todo delete form
                ->asLink('ucms_site.admin.site.view', ['site' => $siteId])
            ;
        }

        return $ret;
    }
}
