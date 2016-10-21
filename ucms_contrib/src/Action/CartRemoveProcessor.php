<?php

namespace MakinaCorpus\Ucms\Contrib\Action;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Contrib\Cart\CartStorageInterface;
use MakinaCorpus\Ucms\Dashboard\SmartObject;
use MakinaCorpus\Ucms\SmartUI\Action\AbstractAjaxProcessor;
use MakinaCorpus\Ucms\SmartUI\Ajax\CartRefreshCommand;

/**
 * Class CartRemoveProcessor
 * @package Action
 */
class CartRemoveProcessor extends AbstractAjaxProcessor
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
     * CartRemoveProcessor constructor.
     *
     * @param \MakinaCorpus\Ucms\Contrib\Cart\CartStorageInterface $cartStorage
     * @param \Drupal\Core\Session\AccountInterface $currentUser
     */
    public function __construct(CartStorageInterface $cartStorage, AccountInterface $currentUser)
    {
        parent::__construct(t("Remove from cart"), 'trash', -10);

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
        && $item->getContext() === SmartObject::CONTEXT_CART
        && $this->cartStorage->has($this->currentUser->id(), $item->getNode()->id());
    }

    /**
     * {@inheritDoc}
     */
    public function process($item, AjaxResponse $response)
    {
        // Better re-check for cart
        if (!$this->cartStorage->has($this->currentUser->id(), $item->getNode()->id())) {
            $response->addCommand(new CartRefreshCommand());
        } else {
            $this->cartStorage->removeFor($this->currentUser->id(), $item->getNode()->id());
            $response->addCommand(new RemoveCommand('#ucms-cart-list [data-nid='.$item->getNode()->id().']'));
        }
    }
}
