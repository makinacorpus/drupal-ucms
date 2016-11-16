<?php

namespace MakinaCorpus\Ucms\Contrib\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Ucms\Contrib\Cart\CartStorageInterface;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Site\Access;
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

    /**
     * @var CartStorageInterface
     */
    private $cart;

    public function __construct(NodeAccessService $access, SiteManager $siteManager, AccountInterface $account, CartStorageInterface $cart)
    {
        $this->access = $access;
        $this->siteManager = $siteManager;
        $this->account = $account;
        $this->cart = $cart;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        /* @var $item NodeInterface */

        // Select the most revelant site to view node in, avoiding the user to
        // view nodes on master site.
        $siteId = $this->access->findMostRelevantSiteFor($item);
        if ($siteId) {
            $ret[] = new Action($this->t("View"), 'sso/goto/' . $siteId, ['query' => ['destination' => 'node/' . $item->id()]], 'eye-open');
        } else {
            $ret[] = new Action($this->t("View"), 'node/' . $item->id(), null, 'eye-open', 0, true, false, true); // disabled link
        }

        // @todo
        //   - if there is a site context:
        //      - if node site is current: just display "edit" and NOT "copy on edit"
        //      - if node site is different: juste display "copy on edit" (if clonable) but NOT "edit"
        //   - no site context (admin):
        //      - if can edit, just display "edit" and NOT "copy on edit"
        //      - if can not edit, don't care, the user has "use on my site" do NOT "copy on edit"

        if ($this->isGranted($item, $this->account, [Permission::UPDATE, Permission::CLONE])) {
            $ret[] = new Action($this->t("Edit"), 'node/' . $item->id() . '/duplicate', 'dialog', 'pencil', -100, false, !$this->siteManager->hasContext(), false, 'edit');

            if ($this->isGranted($item, $this->account, Permission::PUBLISH)) {
                if ($item->status) {
                    $ret[] = new Action($this->t("Unpublish"), 'node/' . $item->id() . '/unpublish', 'dialog', 'remove-circle', -50, false, true, false, 'edit');
                } else {
                    $ret[] = new Action($this->t("Publish"), 'node/' . $item->id() . '/publish', 'dialog', 'ok-circle', -50, false, true, false, 'edit');
                }
            }

            $ret[] = new Action($this->t("Revisions"), 'node/' . $item->id() . '/revisions', null, 'th-list', -10, false, false, false, 'view');
        }

        if (
            $this->account->hasPermission(Access::PERM_CONTENT_TRANSFER_OWNERSHIP) &&
            $item->access(Permission::UPDATE, $this->account)
        ) {
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

        if ($this->account->hasPermission('use favorites')) { // @todo constant or helper ?
            $inCart = $this->cart->has($this->account->id(), $item->id());
            $ret[] = Action::create([
                'title'     => $inCart ? $this->t("Remove from cart") : $this->t("Add to cart"),
                'uri'       => 'admin/cart/' . $item->id() . ($inCart ? '/remove' : '/add') . '/nojs',
                // 'options'   => 'ajax',
                'icon'      => 'shopping-cart',
                'primary'   => false,
                'priority'  => -25,
                'redirect'  => true,
                'group'     => 'mark',
            ]);
        }

        if (!$item->is_global && user_access(Access::PERM_CONTENT_MANAGE_GLOBAL)) {
            $ret[] = new Action($this->t("Add to global contents"), 'node/' . $item->id() . '/make-global', 'dialog', 'globe', -30, false, true, false, 'edit');
        }

        if ($this->account->hasPermission(Access::PERM_CONTENT_MANAGE_STARRED)) {
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

        if (empty($item->is_flagged) && $this->account->hasPermission(Access::PERM_CONTENT_FLAG)) {
            $ret[] = new Action($this->t("Flag as inappropriate"), 'node/' . $item->id() . '/report', 'dialog', 'flag', -10, false, true, false, 'mark');
        } else if (!empty($item->is_flagged) && $this->account->hasPermission(Access::PERM_CONTENT_UNFLAG) && $item->access(Permission::UPDATE))  {
            $ret[] = new Action($this->t("Un-flag as innappropriate"), 'node/' . $item->id() . '/unreport', 'dialog', 'flag', -10, false, true, false, 'mark');
        }

        if ($item->access('delete')) {
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
