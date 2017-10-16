<?php

namespace MakinaCorpus\Ucms\Cart\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use MakinaCorpus\Calista\Action\AbstractActionProvider;
use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Ucms\Cart\Cart\CartStorageInterface;

class NodeActionProvider extends AbstractActionProvider
{
    use StringTranslationTrait;

    /**
     * @var AccountInterface
     */
    private $account;

    /**
     * @var CartStorageInterface
     */
    private $cart;

    public function __construct(AccountInterface $account, CartStorageInterface $cart)
    {
        $this->account = $account;
        $this->cart = $cart;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item, $primaryOnly = false, array $groups = [])
    {
        $ret = [];

        if ($this->isGranted('use favorites')) { // @todo constant or helper ?
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
