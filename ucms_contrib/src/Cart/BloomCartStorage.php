<?php

namespace MakinaCorpus\Ucms\Contrib\Cart;

use Drupal\Core\Cache\CacheBackendInterface;

use MakinaCorpus\Bloom\BloomFilter;

final class BloomCartStorage implements CartStorageInterface
{
    private $cart;
    private $cache;
    private $size;
    private $probability;
    private $filters = [];

    /**
     * Default constructor
     *
     * @param CartStorageInterface $cart
     * @param CacheBackendInterface $cache
     * @param int $size
     * @param float $probibility
     */
    public function __construct(CartStorageInterface $cart, CacheBackendInterface $cache, $size = 2000, $probibility = 0.001)
    {
        if ($cart->isReadonly()) {
            throw new \LogicException("bloom cart is useless with a readonly cart");
        }

        $this->cart = $cart;
        $this->cache = $cache;
        $this->size = $size;
        $this->probability = $probibility;
    }

    /**
     * Get cache identifier
     *
     * @param int $uid
     *
     * @return string
     */
    private function getCacheId($uid)
    {
        return 'ucms:cart:' . $uid;
    }

    /**
     * Rebuild new filter from 0 for the given user
     *
     * @param int $uid
     */
    private function rebuildFilter($uid)
    {
        $filter = new BloomFilter($this->size, $this->probability);

        foreach ($this->listFor($uid) as $item) {
            $filter->set($item->getNodeId());
        }

        $this->cache->set($this->getCacheId($uid), $filter);

        return $filter;
    }

    /**
     * Get filter for user
     *
     * @param int $uid
     *
     * @return BloomFilter
     */
    private function getFilter($uid)
    {
        if (isset($this->filters[$uid])) {
            return $this->filters[$uid];
        }

        $entry = $this->cache->get($this->getCacheId($uid));

        if ($entry && $entry->data instanceof BloomFilter) {
            return $this->filters[$uid] = $entry->data;
        }

        return $this->filters[$uid] = $this->rebuildFilter($uid);
    }

    /**
     * Save filter for user
     *
     * @param int $uid
     */
    private function deleteFilter($uid)
    {
        unset($this->filters[$uid]);
        $this->cache->delete($this->getCacheId($uid));
    }

    /**
     * {@inheritdoc}
     */
    public function isReadonly()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function addFor($uid, $nid)
    {
        $ret = $this->cart->addFor($uid, $nid);

        $filter = $this->getFilter($uid);
        $filter->set($nid);
        $this->cache->set($this->getCacheId($uid), $filter);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function has($uid, $nid)
    {
        return $this->getFilter($uid)->check($nid);
    }

    /**
     * {@inheritdoc}
     */
    public function removeFor($uid, $nid)
    {
        $this->cart->removeFor($uid, $nid);

        // Removing items in a bloom filter is not possible
        $this->deleteFilter($uid);
    }

    /**
     * {@inheritdoc}
     */
    public function listFor($uid, $limit = 14, $offset = 0)
    {
        return $this->cart->listFor($uid, $limit, $offset);
    }
}
