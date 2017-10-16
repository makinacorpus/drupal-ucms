<?php

namespace MakinaCorpus\Ucms\Cart\Cart;

use Drupal\Core\Entity\EntityManager;

/**
 * Proxifies any cart storage, and preload nodes into items
 */
class LoaderCartStorageProxy implements CartStorageInterface
{
    private $cart;
    private $entityManager;

    /**
     * Default constructor
     *
     * @param CartStorageInterface $cart
     */
    public function __construct(EntityManager $entityStorage, CartStorageInterface $cart)
    {
        $this->cart = $cart;
        $this->entityManager = $entityStorage;
    }

    /**
     * {@inhertdoc}
     */
    public function addFor($uid, $nid)
    {
        return $this->cart->addFor($uid, $nid);
    }

    /**
     * {@inhertdoc}
     */
    public function has($uid, $nid)
    {
        return $this->cart->has($uid, $nid);
    }

    /**
     * {@inhertdoc}
     */
    public function removeFor($uid, $nid)
    {
        return $this->cart->removeFor($uid, $nid);
    }

    /**
     * {@inhertdoc}
     */
    public function listFor($uid, $limit = 14, $offset = 0)
    {
        $items = $this->cart->listFor($uid, $limit, $offset);

        if ($items) {
            $nodeIdList = [];

            // I don't like foreach()ing twice, but it will never display more
            // than a few dozens of items, so it doesn't really make it slow,
            // and avoid LOTS of database roundtrips.
            foreach ($items as $item) {
                $nodeIdList[] = $item->getNodeId();
            }

            $nodes = $this->entityManager->getStorage('node')->loadMultiple($nodeIdList);

            foreach ($items as $index => $item) {
                $nodeId = $item->getNodeId();

                if (isset($nodes[$nodeId])) {
                    $item->setNode($nodes[$nodeId]);
                } else {
                    // This should almost never happen, because they are foreign
                    // key constraints on the database, but in real life during
                    // concurrency scenarios, it may happen that a node is been
                    // deleted between original load query and now.
                    unset($items[$index]);
                }
            }
        }

        return $items;
    }
}
