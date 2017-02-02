<?php

namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Drupal\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Drupal\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Site\NodeAccessService;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * The site module will add node actions, corresponding to reference
 * and cloning operations
 */
class NodeActionProvider extends AbstractActionProvider
{
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
    public function getActions($item)
    {
        $ret = [];

        /* @var $item NodeInterface */
        $account = $this->currentUser;

        // Check if current content is a reference within the current context
        if ($this->siteManager->hasContext()) {
            $site = $this->siteManager->getContext();

            if ($this->nodeAccess->userCanDereference($account, $item, $site)) {
                $ret[] = new Action($this->t("Remove from the current site"), 'node/' . $item->nid . '/dereference-from/' . $site->getId(), 'dialog', 'remove', 2, true, true, false, 'site');
            }
            if ($this->nodeAccess->userCanReference($account, $item)) {
                $ret[] = new Action($this->t("Use on another site"), 'node/' . $item->nid . '/reference', 'dialog', 'download-alt', 2, true, true, false, 'site');
            }

        } else if ($this->nodeAccess->userCanReference($account, $item)) {
            // We are not on a site, just display "normal" action
            $ret[] = new Action($this->t("Use on my site"), 'node/' . $item->nid . '/reference', 'dialog', 'download-alt', 2, true, true, false, 'site');
        }

        if ($this->isGranted(Permission::LOCK, $item)) {
            if ($item->is_clonable) {
                $ret[] = new Action($this->t("Lock"), 'node/' . $item->id() . '/lock', 'dialog', 'lock', 2, false, true, false, 'edit');
            } else {
                $ret[] = new Action($this->t("Unlock"), 'node/' . $item->id() . '/unlock', 'dialog', 'lock', 2, false, true, false, 'edit');
            }
        }

        $ret[] = new Action($this->t("View in site"), 'node/' . $item->id() . '/site-list', 'dialog', 'search', 100, false, true, false, 'view');

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof NodeInterface;
    }
}
