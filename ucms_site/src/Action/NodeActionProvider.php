<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * The site module will add node actions, corresponding to reference
 * and cloning operations
 */
class NodeActionProvider extends AbstractActionProvider
{
    private $siteManager;
    private $nodeAccess;

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

        if ($item instanceof NodeInterface) {

            $nodeId = (int)$item->id();
            /*
            $nodeSiteId = (int)$item->get('site_id')->value;
            $nodeIsClonable = (bool)$item->get('is_clonable')->value;
             */

            // Check if current content is a reference within the current context
            if ($this->siteManager->hasContext()) {
                $site = $this->siteManager->getContext();

                /*
                if ($this->nodeAccess->userCanDereference($account, $item, $site)) {
                    $ret[] = new Action($this->t("Remove from the current site"), 'node/'.$nodeId.'/dereference-from/' . $site->getId(), 'dialog', 'remove', 2, true, true, false, 'site');
                }
                if ($this->nodeAccess->userCanReference($account, $item)) {
                    $ret[] = new Action($this->t("Use on another site"), 'node/'.$nodeId.'/reference', 'dialog', 'download-alt', 2, true, true, false, 'site');
                }
                 */

                if ($site->contains($item) && $site->getHomeNodeId() !== $nodeId) {
                    $ret[] = $this
                        ->create('node.set_as_home', new TranslatableMarkup("Use as home page"), 'globe')
                        ->priority(-100)
                        ->redirectHere()
                        ->isGranted(function () use ($site) {
                            return $this->isGranted(Access::OP_UPDATE, $site);
                        })
                        ->identity('node', $nodeId)
                        ->asAction(function () use ($item, $site) {
                            $site->setHomeNodeId($item->id());
                            $this->siteManager->getStorage()->save($site, ['home_nid']);
                        });
                    ;
                }

            } /* else if ($this->nodeAccess->userCanReference($account, $item)) {
                // We are not on a site, just display "normal" action
                $ret[] = new Action($this->t("Use on my site"), 'node/'.$nodeId.'/reference', 'dialog', 'download-alt', 2, true, true, false, 'site');
            } */

            /*
            if ($this->nodeAccess->userCanLock($account, $item)) {
                if ($nodeIsClonable) {
                    $ret[] = new Action($this->t("Lock"), 'node/'.$nodeId.'/lock', 'dialog', 'lock', 2, false, true, false, 'edit');
                } else {
                    $ret[] = new Action($this->t("Unlock"), 'node/'.$nodeId.'/unlock', 'dialog', 'lock', 2, false, true, false, 'edit');
                }
            }
             */

            // $ret[] = new Action($this->t("View in site"), 'node/' . $item->id() . '/site-list', 'dialog', 'search', 100, false, true, false, 'view');
        }

        return $ret;
    }
}
