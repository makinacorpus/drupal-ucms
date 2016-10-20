<?php

namespace MakinaCorpus\Ucms\Contrib\Action;


use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Ucms\Contrib\Cart\CartStorageInterface;
use MakinaCorpus\Ucms\Dashboard\SmartObject;
use MakinaCorpus\Ucms\SmartUI\Action\AbstractAjaxProcessor;

/**
 * Class CartAddProcessor
 * @package MakinaCorpus\Ucms\Contrib\Action
 */
class CartAddProcessor extends AbstractAjaxProcessor
{
    /**
     * @var \MakinaCorpus\Ucms\Contrib\Cart\CartStorage
     */
    private $cartStorage;
    /**
     * @var \Drupal\Core\Session\AccountInterface
     */
    private $currentUser;

    /**
     * CartAddProcessor constructor.
     * @param \MakinaCorpus\Ucms\Contrib\Cart\CartStorageInterface $cartStorage
     * @param \Drupal\Core\Session\AccountInterface $currentUser
     */
    public function __construct(CartStorageInterface $cartStorage, AccountInterface $currentUser)
    {
        parent::__construct(t("Add to cart"), 'cart', -10);

        $this->cartStorage = $cartStorage;
        $this->currentUser = $currentUser;
    }

    /**
     * {@inheritDoc}
     */
    public function appliesTo($item)
    {
        // No need to add to cart if in cart context or already in cart
        return $item instanceof SmartObject
        && $item->getContext() !== SmartObject::CONTEXT_CART
        && !$this->cartStorage->has($this->currentUser->id(), $item->getNode()->id());
    }

    /**
     * {@inheritDoc}
     */
    public function process($item)
    {
        // Better re-check for cart
        if ($this->cartStorage->has($this->currentUser->id(), $item->getNode()->id())) {
            return ucms_smartui_command_cart_reload();
        } else {
            $this->cartStorage->addFor($this->currentUser->id(), $item->getNode()->id());

            return ajax_command_append('##ucms-cart-list', node_view($item->getNode(), UCMS_VIEW_MODE_FAVORITE));
        }
    }
}
