<?php

namespace MakinaCorpus\Ucms\Contrib\Drop;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Drop\DragItem;
use MakinaCorpus\Ucms\Drop\DropStatus;
use MakinaCorpus\Ucms\Drop\Impl\AbstractNodeDropHandler;
use MakinaCorpus\Ucms\Contrib\CartStorage;

class CartDropHandler extends AbstractNodeDropHandler
{
    private $storage;
    private $currentUser;

    /**
     * Default constructor
     *
     * @param EntityManager $entityManager
     */
    public function __construct(CartStorage $storage, AccountInterface $currentUser, EntityManager $entityManager)
    {
        parent::__construct($entityManager, null, 'favorite');

        $this->storage = $storage;
        $this->currentUser = $currentUser;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'cart';
    }

    /**
     * {@inheritdoc}
     */
    public function accepts($id, DragItem $target)
    {
        return 'node' === $target->getType();
    }

    /**
     * {@inheritdoc}
     */
    public function drop($id, DragItem $target, $options = [])
    {
        $this->storage->addFor($this->currentUser->id(), $target->getId());

        return new DropStatus('', false, false, false);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id, DragItem $target)
    {
        $this->storage->removeFor($this->currentUser->id(), $target->getId());

        return new DropStatus('', false, false, false);
    }
}
