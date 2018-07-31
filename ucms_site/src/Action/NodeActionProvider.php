<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\NodeAccessService;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * The site module will add node actions, corresponding to reference
 * and cloning operations
 */
class NodeActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    private $siteManager;
    private $nodeAccess;
    private $currentUser;

    /**
     * Default constructor
     *
     * @param SiteManager $mananger
     * @param NodeAccessService $nodeAccess
     * @param AccountInterface $currentUser
     */
    public function __construct(SiteManager $siteManager, NodeAccessService $nodeAccess, AccountInterface $currentUser)
    {
        $this->siteManager = $siteManager;
        $this->nodeAccess = $nodeAccess;
        $this->currentUser = $currentUser;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item, bool $primaryOnly = false, array $groups = []): array
    {
        $ret = [];

        /** @var \Drupal\node\NodeInterface $item */
        $nodeId = $item->id();
        $nodeSiteId = (int)$item->get('site_id')->value;
        $nodeIsClonable = (bool)$item->get('is_clonable')->value;

        /** @var \Drupal\Core\Session\AccountInterface $account */
        $account = $this->currentUser;

        // Check if current content is a reference within the current context
        /*
        if ($this->siteManager->hasContext()) {
            $site = $this->siteManager->getContext();

            if ($this->nodeAccess->userCanDereference($account, $item, $site)) {
                $ret[] = new Action($this->t("Remove from the current site"), 'node/'.$nodeId.'/dereference-from/' . $site->getId(), 'dialog', 'remove', 2, true, true, false, 'site');
            }
            if ($this->nodeAccess->userCanReference($account, $item)) {
                $ret[] = new Action($this->t("Use on another site"), 'node/'.$nodeId.'/reference', 'dialog', 'download-alt', 2, true, true, false, 'site');
            }

            if (!$site->hasHome() && $nodeSiteId === $site->getId() && $item->access('update', $account)) {
                $ret[] = new Action($this->t("Set as home page"), 'node/'.$nodeId.'/set-home', 'dialog', 'home', 2, false, true, false, 'site');
            }

        } else if ($this->nodeAccess->userCanReference($account, $item)) {
            // We are not on a site, just display "normal" action
            $ret[] = new Action($this->t("Use on my site"), 'node/'.$nodeId.'/reference', 'dialog', 'download-alt', 2, true, true, false, 'site');
        }
         */

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

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item): bool
    {
        return $item instanceof NodeInterface;
    }
}
