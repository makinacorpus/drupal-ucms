<?php

namespace MakinaCorpus\Ucms\Contrib\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Contrib\Cart\CartStorageInterface;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;

class NodeActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

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

    public function __construct(SiteManager $siteManager, AccountInterface $account, CartStorageInterface $cart)
    {
        $this->siteManager = $siteManager;
        $this->access = $this->siteManager->getAccess();
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
        $siteId = $this->siteManager->findMostRelevantSiteFor($item);
        if ($siteId) {
            $uri = $this->siteManager->getUrlGenerator()->generateUrl($siteId, 'node/' . $item->id());
            $ret[] = new Action($this->t("View"), $uri, [], 'eye-open');
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

        if ($item->access(Access::OP_UPDATE, $this->account) || $this->access->userCanDuplicate($this->account, $item)) {
            $ret[] = new Action($this->t("Edit"), 'node/' . $item->id() . '/duplicate', 'dialog', 'pencil', -100, false, !$this->siteManager->hasContext(), false, 'edit');

            if ($this->access->userCanPublish($this->account, $item)) {
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
            $item->access(Access::OP_UPDATE, $this->account)
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
