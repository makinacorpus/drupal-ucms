<?php

namespace MakinaCorpus\Ucms\Contrib\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Site\NodeAccessService;
use MakinaCorpus\Ucms\Site\SiteManager;

class NodeActionProvider extends AbstractActionProvider
{
    /**
     * @var NodeAccessService
     */
    private $access;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var AccountInterface
     */
    private $account;

    public function __construct(NodeAccessService $access, SiteManager $siteManager, AccountInterface $account)
    {
        $this->access = $access;
        $this->siteManager = $siteManager;
        $this->account = $account;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item, $primaryOnly = false, array $groups = [])
    {
        $ret = [];

        /* @var $item NodeInterface */

        // Select the most revelant site to view node in, avoiding the user to
        // view nodes on master site.
        $siteId = $this->access->findMostRelevantSiteFor($item);
        if ($siteId) {
            $uri = $this->siteManager->getUrlGenerator()->generateUrl($siteId, 'node/' . $item->id());
            $ret[] = new Action($this->t("View"), $uri, [], 'eye');
        } else {
            $ret[] = new Action($this->t("View"), 'node/' . $item->id(), null, 'eye', 0, true, false, true); // disabled link
        }

        // @todo
        //   - if there is a site context:
        //      - if node site is current: just display "edit" and NOT "copy on edit"
        //      - if node site is different: juste display "copy on edit" (if clonable) but NOT "edit"
        //   - no site context (admin):
        //      - if can edit, just display "edit" and NOT "copy on edit"
        //      - if can not edit, don't care, the user has "use on my site" do NOT "copy on edit"

        if ($this->isGranted([Permission::UPDATE, Permission::CLONE], $item)) {
            $ret[] = new Action($this->t("Edit"), 'node/' . $item->id() . '/duplicate', 'dialog', 'pencil', -100, false, !$this->siteManager->hasContext(), false, 'edit');

            if ($this->isGranted(Permission::PUBLISH, $item)) {
                if ($item->status) {
                    $ret[] = new Action($this->t("Unpublish"), 'node/' . $item->id() . '/unpublish', 'dialog', 'ban', -50, false, true, false, 'edit');
                } else {
                    $ret[] = new Action($this->t("Publish"), 'node/' . $item->id() . '/publish', 'dialog', 'check-circle', -50, false, true, false, 'edit');
                }
            }

            $ret[] = new Action($this->t("Revisions"), 'node/' . $item->id() . '/revisions', null, 'th-list', -10, false, false, false, 'view');
        }

        if ($this->isGranted(Access::PERM_CONTENT_TRANSFER_OWNERSHIP) && $this->isGranted(Permission::UPDATE, $item)) {
            $ret[] = Action::create([
                'title'     => $this->t("Transfer ownership"),
                'uri'       => 'node/' . $item->id() . '/transfer',
                'options'   => 'dialog',
                'icon'      => 'transfer',
                'primary'   => false,
                'priority'  => 0,
                'redirect'  => true,
                'group'     => 'edit',
            ]);
        }

        /* @var $item NodeInterface */
        if ($item->is_global && user_access(Access::PERM_CONTENT_MANAGE_GROUP)) {
            $ret[] = Action::create([
                'title'     => $item->is_group ? $this->t("Remove from group contents") : $this->t("Define as group content"),
                'uri'       => 'node/' . $item->id() . ($item->is_group ? '/unmake-group' : '/make-group'),
                'options'   => 'dialog',
                'icon'      => 'briefcase',
                'primary'   => false,
                'priority'  => -30,
                'redirect'  => true,
                'group'     => 'edit',
            ]);
        }

        if (!$item->is_global && $this->isGranted(Access::PERM_CONTENT_MANAGE_GLOBAL)) {
            $ret[] = new Action($this->t("Add to global contents"), 'node/' . $item->id() . '/make-global', 'dialog', 'globe', -30, false, true, false, 'edit');
        }

        if ($this->isGranted(Access::PERM_CONTENT_MANAGE_STARRED)) {
            $ret[] = Action::create([
                'title'     => $item->is_starred ? $this->t("Unstar") : $this->t("Star"),
                'uri'       => 'node/' . $item->id() . ($item->is_starred ? '/unstar' : '/star'),
                'options'   => 'dialog',
                'icon'      => $item->is_starred ? 'star-empty' : 'star',
                'primary'   => false,
                'priority'  => -20,
                'redirect'  => true,
                'group'     => 'mark',
            ]);
        }

        if (empty($item->is_flagged) && $this->isGranted(Access::PERM_CONTENT_FLAG)) {
            $ret[] = new Action($this->t("Flag as inappropriate"), 'node/' . $item->id() . '/report', 'dialog', 'flag', -10, false, true, false, 'mark');
        } else if (!empty($item->is_flagged) && $this->isGranted(Access::PERM_CONTENT_UNFLAG) && $this->isGranted(Permission::UPDATE, $item))  {
            $ret[] = new Action($this->t("Un-flag as innappropriate"), 'node/' . $item->id() . '/unreport', 'dialog', 'flag', -10, false, true, false, 'mark');
        }

        if ($this->isGranted(Permission::DELETE, $item)) {
            $ret[] = new Action($this->t("Delete"), 'node/' . $item->id() . '/delete', 'dialog', 'trash', 500, false, true, false, 'delete');
        }

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
